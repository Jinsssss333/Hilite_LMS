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
use App\Models\SlaPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StageTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Team $team;
    protected User $salesperson;
    protected User $otherSalesperson;
    protected User $teamLead;
    protected Lead $lead;
    protected LeadEngagement $engagement;
    protected array $stages = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
        ]);

        $this->team = Team::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Alpha Team',
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

        $this->teamLead = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'team_id' => $this->team->id,
            'name' => 'Test TL',
            'email' => 'tl@test.com',
            'password' => bcrypt('password'),
            'role' => 'team_lead',
        ]);

        // Create pipeline stages
        $stageData = [
            ['name' => 'New',                  'order' => 1, 'color' => '#6366f1', 'sla_days' => 2,    'is_closed' => false],
            ['name' => 'Contacted',            'order' => 2, 'color' => '#f59e0b', 'sla_days' => 5,    'is_closed' => false],
            ['name' => 'Interested',           'order' => 3, 'color' => '#10b981', 'sla_days' => 7,    'is_closed' => false],
            ['name' => 'Site Visit Scheduled', 'order' => 4, 'color' => '#3b82f6', 'sla_days' => 3,    'is_closed' => false],
            ['name' => 'Negotiation',          'order' => 5, 'color' => '#8b5cf6', 'sla_days' => 10,   'is_closed' => false],
            ['name' => 'Booked',               'order' => 6, 'color' => '#22c55e', 'sla_days' => null,  'is_closed' => true],
            ['name' => 'Lost',                 'order' => 7, 'color' => '#ef4444', 'sla_days' => null,  'is_closed' => true],
        ];

        foreach ($stageData as $s) {
            $this->stages[$s['name']] = PipelineStage::create(array_merge($s, [
                'company_id' => $this->company->id,
            ]));
        }

        // Create a lead and engagement
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
            'stage_id' => $this->stages['New']->id,
            'source' => 'manual',
            'status' => 'active',
            'last_activity_at' => now()->subDay(),
            'sla_due_at' => now()->addDays(2),
            'sla_breached' => false,
        ]);

        // Bind company for global scope
        app()->instance('current_company_id', $this->company->id);
    }

    // ---- Ownership & Basic Transition Tests ----

    public function test_assigned_salesperson_can_move_stage_successfully(): void
    {
        $response = $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
                'notes' => 'Called the client',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'engagement_id' => $this->engagement->id,
                    'current_stage' => ['name' => 'Contacted'],
                ],
                'message' => 'Stage updated',
            ]);

        $this->engagement->refresh();
        $this->assertEquals($this->stages['Contacted']->id, $this->engagement->stage_id);
    }

    public function test_non_owner_salesperson_gets_403(): void
    {
        $response = $this->actingAs($this->otherSalesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You do not own this lead. Contact your Team Lead.',
            ]);
    }

    public function test_team_lead_can_move_stage_of_any_engagement(): void
    {
        $response = $this->actingAs($this->teamLead)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_stage_transition_sets_correct_sla_due_date_from_policy(): void
    {
        // Create a company-specific SLA policy overriding the stage default
        SlaPolicy::create([
            'company_id' => $this->company->id,
            'stage_id' => $this->stages['Contacted']->id,
            'sla_days' => 10, // Override stage default of 5
            'escalate_to_role' => 'team_lead',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
            ]);

        $this->engagement->refresh();
        $this->assertEquals(
            Carbon::parse('2026-06-25 12:00:00')->toDateString(),
            $this->engagement->sla_due_at->toDateString()
        );

        Carbon::setTestNow();
    }

    public function test_stage_transition_to_closed_stage_sets_status_to_closed(): void
    {
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Booked']->id,
            ]);

        $this->engagement->refresh();
        $this->assertEquals('closed', $this->engagement->status);
    }

    public function test_stage_transition_logs_audit_entry(): void
    {
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
            ]);

        $auditLog = AuditLog::where('engagement_id', $this->engagement->id)
            ->where('action', 'stage_changed')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('New', $auditLog->before['stage']);
        $this->assertEquals('Contacted', $auditLog->after['stage']);
        $this->assertTrue($auditLog->after['forward']);
    }

    public function test_stage_transition_updates_last_activity_at(): void
    {
        $oldTimestamp = $this->engagement->last_activity_at;

        Carbon::setTestNow(Carbon::now()->addMinutes(5));

        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
            ]);

        $this->engagement->refresh();
        $this->assertTrue($this->engagement->last_activity_at->gt($oldTimestamp));

        Carbon::setTestNow();
    }

    // ---- SLA Gaming Protection Tests ----

    public function test_forward_move_recalculates_sla_and_clears_breached(): void
    {
        // Set engagement as breached
        $this->engagement->update(['sla_breached' => true]);

        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));

        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id, // New → Contacted (forward)
            ]);

        $this->engagement->refresh();
        $this->assertFalse($this->engagement->sla_breached);
        // Contacted has sla_days=5
        $this->assertEquals(
            Carbon::parse('2026-06-20 12:00:00')->toDateString(),
            $this->engagement->sla_due_at->toDateString()
        );

        Carbon::setTestNow();
    }

    public function test_backward_move_does_not_change_sla(): void
    {
        // First move forward: New → Contacted
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
            ]);

        $this->engagement->refresh();
        $slaDueAfterForward = $this->engagement->sla_due_at->copy();

        // Set it to breached
        $this->engagement->update(['sla_breached' => true]);

        // Now move backward: Contacted → New
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['New']->id,
            ]);

        $this->engagement->refresh();
        // SLA should NOT have been reset
        $this->assertTrue($this->engagement->sla_breached);
        $this->assertEquals(
            $slaDueAfterForward->toIso8601String(),
            $this->engagement->sla_due_at->toIso8601String()
        );
    }

    public function test_breached_engagement_backward_then_forward_clears_only_on_forward(): void
    {
        // Move forward first: New → Contacted
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
            ]);

        // Mark as breached
        $this->engagement->refresh();
        $this->engagement->update(['sla_breached' => true]);

        // Move backward: Contacted → New (should NOT clear breached)
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['New']->id,
            ]);

        $this->engagement->refresh();
        $this->assertTrue($this->engagement->sla_breached);

        // Verify audit log for backward move has forward: false
        $backwardAudit = AuditLog::where('engagement_id', $this->engagement->id)
            ->where('action', 'stage_changed')
            ->latest()
            ->first();
        $this->assertFalse($backwardAudit->after['forward']);

        // Move forward again: New → Interested (should clear breached)
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Interested']->id,
            ]);

        $this->engagement->refresh();
        $this->assertFalse($this->engagement->sla_breached);
    }

    public function test_lateral_move_same_order_does_not_reset_sla(): void
    {
        // Create a second stage with same order as "Contacted" (order=2)
        $lateralStage = PipelineStage::create([
            'company_id' => $this->company->id,
            'name' => 'Contacted Alt',
            'order' => 2,
            'color' => '#f59e0b',
            'sla_days' => 3,
            'is_closed' => false,
        ]);

        // Move to Contacted first
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
            ]);

        $this->engagement->refresh();
        $slaDueAfterContacted = $this->engagement->sla_due_at->copy();
        $this->engagement->update(['sla_breached' => true]);

        // Move laterally to same-order stage
        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $lateralStage->id,
            ]);

        $this->engagement->refresh();
        // SLA should NOT have been reset (lateral = not forward)
        $this->assertTrue($this->engagement->sla_breached);
        $this->assertEquals(
            $slaDueAfterContacted->toIso8601String(),
            $this->engagement->sla_due_at->toIso8601String()
        );
    }

    public function test_stage_transition_with_disposition_creates_activity(): void
    {
        $disposition = Disposition::create([
            'company_id' => $this->company->id,
            'stage_id' => $this->stages['Contacted']->id,
            'label' => 'Callback Requested',
        ]);

        $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => $this->stages['Contacted']->id,
                'disposition_id' => $disposition->id,
                'notes' => 'Client wants callback Thursday',
            ]);

        $activity = Activity::where('engagement_id', $this->engagement->id)->first();
        $this->assertNotNull($activity);
        $this->assertEquals('note', $activity->type);
        $this->assertEquals($disposition->id, $activity->disposition_id);
        $this->assertEquals('Client wants callback Thursday', $activity->notes);
    }

    public function test_invalid_stage_id_returns_422(): void
    {
        $response = $this->actingAs($this->salesperson)
            ->patchJson("/api/engagements/{$this->engagement->id}/stage", [
                'stage_id' => 99999,
            ]);

        $response->assertStatus(422);
    }
}
