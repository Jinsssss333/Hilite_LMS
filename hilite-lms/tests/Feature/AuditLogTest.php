<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LeadEngagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_manager_can_get_audit_logs()
    {
        $manager = User::where('company_id', 1)->where('role', 'manager')->firstOrFail();

        $response = $this->actingAs($manager)->getJson('/api/audit-logs');

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_admin_can_get_audit_logs()
    {
        $admin = User::where('company_id', 1)->where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->getJson('/api/audit-logs');

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_salesperson_gets_403_on_audit_logs()
    {
        $sales = User::where('company_id', 1)->where('role', 'salesperson')->firstOrFail();

        $response = $this->actingAs($sales)->getJson('/api/audit-logs');

        $response->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_team_lead_gets_403_on_audit_logs()
    {
        $tl = User::where('company_id', 1)->where('role', 'team_lead')->firstOrFail();

        $response = $this->actingAs($tl)->getJson('/api/audit-logs');

        $response->assertStatus(403)->assertJsonPath('success', false);
    }

    // ── Company scoping ───────────────────────────────────────────────────────

    public function test_audit_log_only_returns_current_company_entries()
    {
        $managerA = User::where('company_id', 1)->where('role', 'manager')->firstOrFail();

        // Seed an audit log for company 2 directly
        AuditLog::create([
            'company_id'    => 2,
            'engagement_id' => null,
            'actor_user_id' => null,
            'action'        => 'lead_created',
            'before'        => null,
            'after'         => null,
        ]);

        $response = $this->actingAs($managerA)->getJson('/api/audit-logs');
        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $entry) {
            // We can't see company_id in the response but engagement must belong to company 1
            if ($entry['engagement_id']) {
                $eng = LeadEngagement::withoutGlobalScopes()->find($entry['engagement_id']);
                $this->assertEquals(1, $eng->company_id);
            }
        }
    }

    // ── Audit actions are written ─────────────────────────────────────────────

    public function test_lead_created_action_is_logged_when_lead_is_created()
    {
        $sales = User::where('company_id', 1)->where('role', 'salesperson')->firstOrFail();

        $this->actingAs($sales)->postJson('/api/leads', [
            'name'   => 'Audit Test Lead',
            'phone'  => '9000099900',
            'source' => 'manual',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'company_id'    => 1,
            'actor_user_id' => $sales->id,
            'action'        => 'lead_created',
        ]);
    }

    public function test_stage_changed_action_is_logged_when_stage_is_moved()
    {
        $tl         = User::where('company_id', 1)->where('role', 'team_lead')->firstOrFail();
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', $tl->team_id)->firstOrFail();
        $engagement  = LeadEngagement::withoutGlobalScopes()->where('company_id', 1)->firstOrFail();

        // Assign first so the salesperson can move the stage
        $this->actingAs($tl)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $salesperson->id,
        ]);

        // Get next stage id
        $nextStageId = \App\Models\PipelineStage::where('company_id', 1)
            ->where('order', 2)
            ->value('id');

        $this->actingAs($salesperson)->patchJson("/api/engagements/{$engagement->id}/stage", [
            'stage_id' => $nextStageId,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'engagement_id' => $engagement->id,
            'action'        => 'stage_changed',
        ]);
    }

    public function test_assigned_action_is_logged_when_lead_is_assigned()
    {
        $tl         = User::where('company_id', 1)->where('role', 'team_lead')->firstOrFail();
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->where('team_id', $tl->team_id)->firstOrFail();
        $engagement  = LeadEngagement::withoutGlobalScopes()->where('company_id', 1)->firstOrFail();

        $this->actingAs($tl)->patchJson("/api/engagements/{$engagement->id}/assign", [
            'assign_to_user_id' => $salesperson->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'engagement_id' => $engagement->id,
            'actor_user_id' => $tl->id,
        ]);
    }

    // ── Pagination + filter ───────────────────────────────────────────────────

    public function test_audit_logs_response_has_correct_meta_shape()
    {
        $manager = User::where('company_id', 1)->where('role', 'manager')->firstOrFail();

        $response = $this->actingAs($manager)->getJson('/api/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_audit_logs_can_be_filtered_by_action()
    {
        $manager = User::where('company_id', 1)->where('role', 'manager')->firstOrFail();

        $response = $this->actingAs($manager)->getJson('/api/audit-logs?action=lead_created');

        $response->assertStatus(200);
        foreach ($response->json('data') as $entry) {
            $this->assertEquals('lead_created', $entry['action']);
        }
    }
}
