<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\LeadEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class MarkDormantLeadsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Leads with no activity for this many days become dormant.
     */
    const DORMANT_AFTER_DAYS = 180;

    /**
     * Runs weekly. Marks active engagements with no activity in 180 days as dormant.
     * Writes a system audit log entry for each.
     */
    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays(self::DORMANT_AFTER_DAYS);

        LeadEngagement::withoutGlobalScopes()
            ->where('status', 'active')
            ->where(fn($q) =>
                $q->where('last_activity_at', '<', $cutoff)
                  ->orWhereNull('last_activity_at')
            )
            ->chunkById(100, function ($engagements) {
                foreach ($engagements as $engagement) {
                    $engagement->update([
                        'status'     => 'dormant',
                        'dormant_at' => Carbon::now(),
                    ]);

                    AuditLog::create([
                        'company_id'    => $engagement->company_id,
                        'engagement_id' => $engagement->id,
                        'actor_user_id' => null,
                        'action'        => 'lead_dormant',
                        'before'        => json_encode(['status' => 'active']),
                        'after'         => json_encode(['status' => 'dormant']),
                    ]);
                }
            });
    }
}
