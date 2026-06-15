<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Team;
use App\Models\Disposition;
use App\Models\Lead;
use App\Models\LeadEngagement;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Company $otherCompany;
    protected Branch $branch;
    protected Branch $otherBranch;
    protected Team $team;
    protected Team $otherTeam;
    protected User $salesperson;
    protected User $otherSalesperson;
    protected Lead $lead;
    protected LeadEngagement $engagement;
    protected PipelineStage $newStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'is_active' => true,
        ]);

        $this->otherCompany = Company::create([
            'name' => 'Other Company',
            'slug' => 'other-company',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
        ]);

        $this->otherBranch = Branch::create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Other Branch',
        ]);

        $this->team = Team::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Alpha Team',
        ]);

        $this->otherTeam = Team::create([
            'company_id' => $this->otherCompany->id,
            'branch_id' => $this->otherBranch->id,
            'name' => 'Other Team',
        ]);

        $this->salesperson = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'team_id' => $this->team->id,
            'name' => 'Test Salesperson',
            'email' => 'sales@test.com',
            'password' => bcrypt('password'),
            'role' => 'salesperson',
        ]);

        $this->otherSalesperson = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'team_id' => $this->team->id,
            'name' => 'Other Salesperson',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'role' => 'salesperson',
        ]);

        $this->newStage = PipelineStage::create([
            'company_id' => $this->company->id,
            'name' => 'Contacted',
            'order' => 2,
            'color' => '#f59e0b',
            'sla_days' => 5,
            'is_closed' => false,
        ]);

        $this->lead = Lead::create([
            'phone_e164' => '+919876543210',
            'name' => 'Test Lead',
            'email' => 'lead@test.com',
            'status' => 'active',
        ]);

        $this->engagement = LeadEngagement::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'lead_id' => $this->lead->id,
            'assigned_user_id' => $this->salesperson->id,
            'stage_id' => $this->newStage->id,
            'source' => 'manual',
            'status' => 'active',
            'last_activity_at' => now()->subDay(),
            'sla_due_at' => now()->addDays(5),
            'sla_breached' => false,
        ]);

        app()->instance('current_company_id', $this->company->id);
    }

    // ---- Activity Creation Tests ----

    public function test_owner_can_create_note_activity(): void
    {
        $response = $this->actingAs($this->salesperson)
            ->postJson("/api/engagements/{$this->engagement->id}/activities", [
                'type' => 'note',
                'notes' => 'Called, interested in 3BHK',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'engagement_id' => $this->engagement->id,
                    'type' => 'note',
                    'notes' => 'Called, interested in 3BHK',
                    'created_by' => [
                        'id' => $this->salesperson->id,
                        'name' => $this->salesperson->name,
                    ],
                ],
                'message' => 'Activity logged',
            ]);
    }

    public function test_owner_can_create_followup_with_future_date(): void
    {
        $futureDate = Carbon::now()->addDays(3)->toIso8601String();

        $response = $this->actingAs($this->salesperson)
            ->postJson("/api/engagements/{$this->engagement->id}/activities", [
                'type' => 'followup',
                'notes' => 'Call back Thursday',
                'follow_up_at' => $futureDate,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'followup',
                ],
            ]);

        $this->assertNotNull($response->json('data.follow_up_at'));
    }

    public function test_past_follow_up_at_date_is_rejected_with_422(): void
    {
        $pastDate = Carbon::now()->subDays(1)->toIso8601String();

        $response = $this->actingAs($this->salesperson)
            ->postJson("/api/engagements/{$this->engagement->id}/activities", [
                'type' => 'followup',
                'notes' => 'Past date',
                'follow_up_at' => $pastDate,
            ]);

        $response->assertStatus(422);
    }

    public function test_non_owner_salesperson_gets_403(): void
    {
        $response = $this->actingAs($this->otherSalesperson)
            ->postJson("/api/engagements/{$this->engagement->id}/activities", [
                'type' => 'note',
                'notes' => 'Should not be allowed',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You do not own this lead.',
            ]);
    }

    public function test_activity_creation_updates_engagement_last_activity_at(): void
    {
        $oldTimestamp = $this->engagement->last_activity_at;

        Carbon::setTestNow(Carbon::now()->addMinutes(5));

        $this->actingAs($this->salesperson)
            ->postJson("/api/engagements/{$this->engagement->id}/activities", [
                'type' => 'call',
                'notes' => 'Brief call',
            ]);

        $this->engagement->refresh();
        $this->assertTrue($this->engagement->last_activity_at->gt($oldTimestamp));

        Carbon::setTestNow();
    }

    public function test_activity_creation_logs_audit_entry(): void
    {
        $this->actingAs($this->salesperson)
            ->postJson("/api/engagements/{$this->engagement->id}/activities", [
                'type' => 'visit',
                'notes' => 'Site visit done',
            ]);

        $audit = AuditLog::where('engagement_id', $this->engagement->id)
            ->where('action', 'activity_logged')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('visit', $audit->after['type']);
    }

    public function test_activity_with_disposition(): void
    {
        $disposition = Disposition::create([
            'company_id' => $this->company->id,
            'stage_id' => null,
            'label' => 'Callback Requested',
        ]);

        $response = $this->actingAs($this->salesperson)
            ->postJson("/api/engagements/{$this->engagement->id}/activities", [
                'type' => 'followup',
                'disposition_id' => $disposition->id,
                'notes' => 'Client asked to call back',
                'follow_up_at' => Carbon::now()->addDays(2)->toIso8601String(),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'disposition' => [
                        'id' => $disposition->id,
                        'label' => 'Callback Requested',
                    ],
                ],
            ]);
    }

    // ---- Upcoming Follow-ups Tests ----

    public function test_upcoming_followups_returns_only_current_users_followups(): void
    {
        // Create follow-up for this salesperson
        Activity::create([
            'engagement_id' => $this->engagement->id,
            'created_by_user_id' => $this->salesperson->id,
            'type' => 'followup',
            'notes' => 'My follow-up',
            'follow_up_at' => Carbon::now()->addDays(2),
        ]);

        // Create engagement assigned to other salesperson
        $otherLead = Lead::create([
            'phone_e164' => '+919876543299',
            'name' => 'Other Lead',
            'status' => 'active',
        ]);

        $otherEngagement = LeadEngagement::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'lead_id' => $otherLead->id,
            'assigned_user_id' => $this->otherSalesperson->id,
            'stage_id' => $this->newStage->id,
            'source' => 'manual',
            'status' => 'active',
            'sla_breached' => false,
        ]);

        Activity::create([
            'engagement_id' => $otherEngagement->id,
            'created_by_user_id' => $this->otherSalesperson->id,
            'type' => 'followup',
            'notes' => 'Not my follow-up',
            'follow_up_at' => Carbon::now()->addDays(3),
        ]);

        $response = $this->actingAs($this->salesperson)
            ->getJson('/api/activities/upcoming');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('My follow-up', $data[0]['notes']);
        $this->assertEquals($this->engagement->id, $data[0]['engagement_id']);
    }

    public function test_upcoming_followups_does_not_return_other_companys_activities(): void
    {
        // Create follow-up for a different company
        $otherStage = PipelineStage::create([
            'company_id' => $this->otherCompany->id,
            'name' => 'New',
            'order' => 1,
            'color' => '#6366f1',
            'sla_days' => 2,
            'is_closed' => false,
        ]);

        $otherUser = User::create([
            'company_id' => $this->otherCompany->id,
            'branch_id' => $this->otherBranch->id,
            'team_id' => $this->otherTeam->id,
            'name' => 'Other Co User',
            'email' => 'otherco@test.com',
            'password' => bcrypt('password'),
            'role' => 'salesperson',
        ]);

        $otherLead = Lead::create([
            'phone_e164' => '+919876543288',
            'name' => 'Other Co Lead',
            'status' => 'active',
        ]);

        $otherEngagement = LeadEngagement::withoutGlobalScopes()->create([
            'company_id' => $this->otherCompany->id,
            'lead_id' => $otherLead->id,
            'assigned_user_id' => $otherUser->id,
            'stage_id' => $otherStage->id,
            'source' => 'manual',
            'status' => 'active',
            'sla_breached' => false,
        ]);

        Activity::create([
            'engagement_id' => $otherEngagement->id,
            'created_by_user_id' => $otherUser->id,
            'type' => 'followup',
            'notes' => 'Other company follow-up',
            'follow_up_at' => Carbon::now()->addDays(2),
        ]);

        // Our company should see nothing
        $response = $this->actingAs($this->salesperson)
            ->getJson('/api/activities/upcoming');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_upcoming_followups_respects_days_parameter(): void
    {
        // Create a follow-up 3 days out
        Activity::create([
            'engagement_id' => $this->engagement->id,
            'created_by_user_id' => $this->salesperson->id,
            'type' => 'followup',
            'notes' => 'Within range',
            'follow_up_at' => Carbon::now()->addDays(3),
        ]);

        // Create a follow-up 10 days out
        Activity::create([
            'engagement_id' => $this->engagement->id,
            'created_by_user_id' => $this->salesperson->id,
            'type' => 'followup',
            'notes' => 'Outside range',
            'follow_up_at' => Carbon::now()->addDays(10),
        ]);

        // Query with days=5 — should only get the first one
        $response = $this->actingAs($this->salesperson)
            ->getJson('/api/activities/upcoming?days=5');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Within range', $data[0]['notes']);
    }
}
