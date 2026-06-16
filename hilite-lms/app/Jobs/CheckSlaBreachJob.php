<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\LeadEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class CheckSlaBreachJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Find all active engagements whose sla_due_at has passed
     * and flag them as breached. Writes a system audit log entry.
     * Runs hourly via the scheduler.
     */
    public function handle(): void
    {
        LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('sla_breached', false)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', Carbon::now())
            ->chunkById(100, function ($engagements) {
                foreach ($engagements as $engagement) {
                    $engagement->update(['sla_breached' => true]);

                    // actor_user_id = null → system-initiated action
                    AuditLog::create([
                        'company_id'    => $engagement->company_id,
                        'engagement_id' => $engagement->id,
                        'actor_user_id' => null,
                        'action'        => 'sla_breached',
                        'before'        => null,
                        'after'         => json_encode(['sla_due_at' => $engagement->sla_due_at]),
                    ]);
                }
            });
    }
}
