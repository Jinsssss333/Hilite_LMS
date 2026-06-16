<?php

namespace Tests\Feature;

use App\Jobs\CheckSlaBreachJob;
use App\Models\LeadEngagement;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Full end-to-end demo flow test.
 * Covers the complete scenario as specified in IMPLEMENTATION_DEV_C.md Day 7.
 *
 * 1.  Login as Company A salesperson → get token
 * 2.  Create a new lead → 201, is_duplicate false
 * 3.  Create same lead again → 200, is_duplicate true
 * 4.  Login as Company A TL → assign lead to salesperson
 * 5.  Login as salesperson → move stage from New to Contacted
 * 6.  Add a follow-up activity
 * 7.  Login as Company B salesperson → try to GET Company A engagement → 404
 * 8.  Run CheckSlaBreachJob with past sla_due_at → engagement marked breached
 * 9.  GET /audit-logs as manager → all key actions present
 */
class FullDemoFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_full_demo_flow()
    {
        // ── Step 1: Login as Company A salesperson ────────────────────────────
        $salesperson = User::where('company_id', 1)->where('role', 'salesperson')->firstOrFail();
        $tl          = User::where('company_id', 1)->where('role', 'team_lead')->where('team_id', $salesperson->team_id)->firstOrFail();
        $manager     = User::where('company_id', 1)->where('role', 'manager')->firstOrFail();
        $salesB      = User::where('company_id', 2)->where('role', 'salesperson')->firstOrFail();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $salesperson->email,
            'password' => 'password123',
        ]);
        $loginResponse->assertStatus(200)->assertJsonPath('success', true);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        // ── Step 2: Create a new lead ─────────────────────────────────────────
        $createResponse = $this->actingAs($salesperson)->postJson('/api/leads', [
            'name'   => 'Demo Flow Lead',
            'phone'  => '9111222333',
            'email'  => 'demo@flow.com',
            'source' => 'manual',
        ]);
        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_duplicate', false);

        $engagementId = $createResponse->json('data.engagement_id');
        $this->assertNotNull($engagementId);

        // ── Step 3: Create same lead again → duplicate ────────────────────────
        $dupResponse = $this->actingAs($salesperson)->postJson('/api/leads', [
            'name'   => 'Demo Flow Lead',
            'phone'  => '9111222333',
            'source' => 'manual',
        ]);
        $dupResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_duplicate', true);

        // ── Step 4: TL assigns lead to salesperson ────────────────────────────
        $assignResponse = $this->actingAs($tl)->patchJson("/api/engagements/{$engagementId}/assign", [
            'assign_to_user_id' => $salesperson->id,
            'reason'            => 'Initial assignment for demo',
        ]);
        $assignResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_to.id', $salesperson->id);

        // ── Step 5: Salesperson moves stage from New → Contacted ──────────────
        $contactedStage = PipelineStage::where('company_id', 1)
            ->where('name', 'Contacted')
            ->firstOrFail();

        $stageResponse = $this->actingAs($salesperson)->patchJson("/api/engagements/{$engagementId}/stage", [
            'stage_id' => $contactedStage->id,
            'notes'    => 'Called — interested in 3BHK',
        ]);
        $stageResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_stage.name', 'Contacted');

        // ── Step 6: Add a follow-up activity ─────────────────────────────────
        $activityResponse = $this->actingAs($salesperson)->postJson("/api/engagements/{$engagementId}/activities", [
            'type'         => 'followup',
            'notes'        => 'Client asked to call back Thursday',
            'follow_up_at' => Carbon::now()->addDays(3)->toIso8601String(),
        ]);
        $activityResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'followup');

        // ── Step 7: Company B salesperson cannot see Company A engagement ──────
        $crossResponse = $this->actingAs($salesB)->getJson("/api/leads/{$engagementId}");
        $crossResponse->assertStatus(404);

        // ── Step 8: SLA breach job with past sla_due_at ───────────────────────
        $engagement = LeadEngagement::withoutGlobalScopes()->find($engagementId);
        $engagement->update([
            'sla_due_at'   => Carbon::now()->subDays(5),
            'sla_breached' => false,
        ]);

        CheckSlaBreachJob::dispatch();

        $this->assertDatabaseHas('lead_engagements', [
            'id'          => $engagementId,
            'sla_breached' => 1,
        ]);

        // ── Step 9: GET /audit-logs as manager → all 4+ key actions present ───
        $auditResponse = $this->actingAs($manager)->getJson('/api/audit-logs?engagement_id=' . $engagementId);
        $auditResponse->assertStatus(200)->assertJsonPath('success', true);

        $actions = collect($auditResponse->json('data'))->pluck('action')->toArray();

        $this->assertContains('lead_created',  $actions);
        $this->assertContains('assigned',       $actions);
        $this->assertContains('stage_changed',  $actions);
        $this->assertContains('sla_breached',   $actions);
    }
}
