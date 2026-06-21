<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadEngagement;
use Carbon\Carbon;

class CheckReplySLA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check-replies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for inbound replies that have not received a manual response within SLA window';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for SLA breaches on inbound replies...');
        
        // Find engagements where the last activity was an inbound webhook/email
        // and no subsequent activity was logged within 24 hours
        $threshold = Carbon::now()->subHours(24);
        
        $engagements = LeadEngagement::where('status', 'active')
            ->whereNotNull('assigned_user_id')
            ->where('sla_breached', false) // Don't process already breached ones
            ->whereHas('activities', function ($query) use ($threshold) {
                // Find the latest activity
                $query->whereIn('type', ['note', 'webhook_reply', 'email_reply'])
                      ->where('occurred_at', '<', $threshold)
                      // Ideally we'd join and check the latest activity per engagement
                      ->orderBy('occurred_at', 'desc');
            })
            ->get();
            
        $breachedCount = 0;
        
        foreach ($engagements as $engagement) {
            $latestActivity = $engagement->activities()->orderBy('occurred_at', 'desc')->first();
            
            // Check if the latest activity was inbound (system user or external)
            // and it occurred before the threshold
            if ($latestActivity && $latestActivity->occurred_at < $threshold && $latestActivity->created_by_user_id !== $engagement->assigned_user_id) {
                // SLA Breach detected!
                $engagement->sla_breached = true;
                $engagement->save();
                
                // TODO: Send notification/alert to team_lead or manager based on SLA Policies
                // This rectifies the "Outbound Loop Trap"
                \App\Models\AuditLog::log(
                    $engagement->company_id,
                    0, // System
                    'lead_engagement',
                    $engagement->id,
                    'sla_breached',
                    'SLA breached on inbound reply. Agent took > 24 hours to respond.'
                );
                
                $breachedCount++;
            }
        }
        
        $this->info("Found {$breachedCount} SLA breaches.");
    }
}
