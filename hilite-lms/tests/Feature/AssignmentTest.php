<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\LeadEngagement;
use App\Models\OwnershipAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function user(string $role, int $companyId = 1, ?int $teamId = 1): User
    {
        return User::where('company_id', $companyId)
            ->where('role', $role)
            ->when($teamId, fn($q) => $q->where('team_id', $teamId))
            ->firstOrFail();
    }

    private function engagement(int $companyId = 1): LeadEngagement
    {
        return LeadEngagement::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->firstOrFail();
    }

    // ── TL scope ─────────────────────────────────────────────────────────────

    public function test_team_lead_can_assign_lead_within_own_team()
    {
        $tl         = $this->user('team_lead', 1, 1);
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', 1)->first();
        $engagement = $this->engagement(1);

        $response = $this->actingAs($tl)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $salesperson->id,
            'reason'            => 'Initial assignment',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_to.id', $salesperson->id);
    }

    public function test_team_lead_cannot_assign_lead_to_user_in_different_team()
    {
        $tl          = $this->user('team_lead', 1, 1);
        $otherSales  = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', 2)->firstOrFail();
        $engagement  = $this->engagement(1);

        $response = $this->actingAs($tl)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $otherSales->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    // ── Manager scope ─────────────────────────────────────────────────────────

    public function test_manager_can_assign_lead_to_anyone_in_company()
    {
        $manager    = $this->user('manager', 1, null);
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', 2)->firstOrFail();
        $engagement  = $this->engagement(1);

        $response = $this->actingAs($manager)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $salesperson->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ── Salesperson scope ─────────────────────────────────────────────────────

    public function test_salesperson_cannot_assign_lead()
    {
        $sales      = User::where('company_id', 1)->where('role', 'salesperson')->firstOrFail();
        $other      = User::where('company_id', 1)->where('role', 'salesperson')->where('id', '!=', $sales->id)->firstOrFail();
        $engagement = $this->engagement(1);

        $response = $this->actingAs($sales)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $other->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    // ── History and audit ─────────────────────────────────────────────────────

    public function test_assignment_is_recorded_in_ownership_assignments_table()
    {
        $tl         = $this->user('team_lead', 1, 1);
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', 1)->first();
        $engagement  = $this->engagement(1);

        $this->actingAs($tl)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $salesperson->id,
            'reason'            => 'test reason',
        ]);

        $this->assertDatabaseHas('ownership_assignments', [
            'engagement_id'       => $engagement->id,
            'assigned_to_user_id' => $salesperson->id,
            'assigned_by_user_id' => $tl->id,
        ]);
    }

    public function test_assignment_logs_audit_entry()
    {
        $tl         = $this->user('team_lead', 1, 1);
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', 1)->first();
        $engagement  = $this->engagement(1);

        $this->actingAs($tl)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $salesperson->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'engagement_id' => $engagement->id,
            'actor_user_id' => $tl->id,
        ]);
    }

    public function test_reassignment_updates_assigned_user_id_on_engagement()
    {
        $tl          = $this->user('team_lead', 1, 1);
        $salesperson1 = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', 1)->skip(0)->first();
        $salesperson2 = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', 1)->skip(1)->first();
        $engagement   = $this->engagement(1);

        // First assignment
        $this->actingAs($tl)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $salesperson1->id,
        ]);

        // Reassign
        $this->actingAs($tl)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $salesperson2->id,
        ]);

        $this->assertDatabaseHas('lead_engagements', [
            'id'               => $engagement->id,
            'assigned_user_id' => $salesperson2->id,
        ]);
    }

    // ── Cross-company isolation ───────────────────────────────────────────────

    public function test_user_from_company_a_cannot_assign_company_b_engagement()
    {
        $managerA   = User::where('company_id', 1)->where('role', 'manager')->firstOrFail();
        $engagementB = LeadEngagement::withoutGlobalScopes()->where('company_id', 2)->firstOrFail();

        $response = $this->actingAs($managerA)->patchJson("/api/engagements/{$engagementB->id}/assign", [
            'assign_to_user_id' => $managerA->id,
        ]);

        // Global scope makes Company B's engagement invisible — 404 or 403
        $response->assertStatus(404);
    }

    // ── Assignable users list ─────────────────────────────────────────────────

    public function test_get_assignable_returns_team_members_for_team_lead()
    {
        $tl = $this->user('team_lead', 1, 1);

        $response = $this->actingAs($tl)->getJson('/api/users/assignable');

        $response->assertStatus(200)->assertJsonPath('success', true);
        // All returned users must be in TL's team
        foreach ($response->json('data') as $u) {
            $dbUser = User::find($u['id']);
            $this->assertEquals(1, $dbUser->team_id);
        }
    }

    public function test_get_assignable_returns_all_company_users_for_manager()
    {
        $manager = $this->user('manager', 1, null);

        $response = $this->actingAs($manager)->getJson('/api/users/assignable');

        $response->assertStatus(200);
        // Should include both teams
        $teamIds = collect($response->json('data'))->pluck('id')
            ->map(fn($id) => User::find($id)->team_id)
            ->unique()
            ->values();

        $this->assertGreaterThan(1, $teamIds->count());
    }

    public function test_get_assignable_returns_empty_for_salesperson()
    {
        $sales = User::where('company_id', 1)->where('role', 'salesperson')->firstOrFail();

        $response = $this->actingAs($sales)->getJson('/api/users/assignable');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }
}
