<?php

namespace Tests\Feature;

use App\Jobs\CheckSlaBreachJob;
use App\Jobs\MarkDormantLeadsJob;
use App\Models\LeadEngagement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlaBreachTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFreshDatabase();
    }

    // ── CheckSlaBreachJob ─────────────────────────────────────────────────────

    public function test_check_sla_breach_job_flags_overdue_engagement()
    {
        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->whereNotNull('sla_due_at')
            ->first();

        if (!$engagement) {
            // Force-set an engagement to have a past SLA date
            $engagement = LeadEngagement::withoutGlobalScopes()
                ->where('status', 'active')
                ->first();
            $engagement->update([
                'sla_due_at'   => Carbon::now()->subDays(3),
                'sla_breached' => false,
            ]);
        }

        // Move SLA to the past
        $engagement->update([
            'sla_due_at'   => Carbon::now()->subHours(2),
            'sla_breached' => false,
        ]);

        CheckSlaBreachJob::dispatch();

        $this->assertDatabaseHas('lead_engagements', [
            'id'          => $engagement->id,
            'sla_breached' => 1,
        ]);
    }

    public function test_check_sla_breach_job_does_not_reflag_already_breached()
    {
        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->first();

        // Already breached — mark it so and set a past SLA
        $engagement->update([
            'sla_due_at'   => Carbon::now()->subDays(5),
            'sla_breached' => true,
        ]);

        $beforeCount = \App\Models\AuditLog::where('action', 'sla_breached')
            ->where('engagement_id', $engagement->id)
            ->count();

        CheckSlaBreachJob::dispatch();

        $afterCount = \App\Models\AuditLog::where('action', 'sla_breached')
            ->where('engagement_id', $engagement->id)
            ->count();

        // No additional sla_breached audit entry should have been created
        $this->assertEquals($beforeCount, $afterCount);
    }

    public function test_check_sla_breach_job_writes_audit_log_entry()
    {
        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->first();

        $engagement->update([
            'sla_due_at'   => Carbon::now()->subHours(1),
            'sla_breached' => false,
        ]);

        CheckSlaBreachJob::dispatch();

        $this->assertDatabaseHas('audit_logs', [
            'engagement_id' => $engagement->id,
            'action'        => 'sla_breached',
            'actor_user_id' => null,
        ]);
    }

    // ── MarkDormantLeadsJob ───────────────────────────────────────────────────

    public function test_mark_dormant_job_marks_inactive_engagement_dormant()
    {
        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->first();

        // Simulate 181 days of inactivity
        $engagement->update([
            'last_activity_at' => Carbon::now()->subDays(181),
        ]);

        MarkDormantLeadsJob::dispatch();

        $this->assertDatabaseHas('lead_engagements', [
            'id'     => $engagement->id,
            'status' => 'dormant',
        ]);
    }

    public function test_mark_dormant_job_writes_audit_entry()
    {
        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->first();

        $engagement->update([
            'last_activity_at' => Carbon::now()->subDays(181),
        ]);

        MarkDormantLeadsJob::dispatch();

        $this->assertDatabaseHas('audit_logs', [
            'engagement_id' => $engagement->id,
            'action'        => 'lead_dormant',
            'actor_user_id' => null,
        ]);
    }

    public function test_mark_dormant_job_does_not_affect_recently_active_engagement()
    {
        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->first();

        // Recent activity — should NOT become dormant
        $engagement->update([
            'last_activity_at' => Carbon::now()->subDays(10),
        ]);

        MarkDormantLeadsJob::dispatch();

        $this->assertDatabaseHas('lead_engagements', [
            'id'     => $engagement->id,
            'status' => 'active',
        ]);
    }

    public function test_dormant_engagement_excluded_from_default_active_leads_list()
    {
        $engagement = LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('company_id', 1)
            ->first();

        $engagement->update([
            'last_activity_at' => Carbon::now()->subDays(181),
        ]);

        MarkDormantLeadsJob::dispatch();

        // The engagement is now dormant; admin listing active leads should not include it
        $admin    = User::where('company_id', 1)->where('role', 'admin')->firstOrFail();
        $response = $this->actingAs($admin)->getJson('/api/leads?status=active');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('engagement_id');
        $this->assertNotContains($engagement->id, $ids->toArray());
    }
}
