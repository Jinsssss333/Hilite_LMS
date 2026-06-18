<?php

namespace App\Jobs;

use App\Models\AssignmentQueue;
use App\Models\LeadEngagement;
use App\Services\Routing\AssignmentEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessAssignmentQueueJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(AssignmentEngine $engine): void
    {
        // Get up to 100 waiting items to prevent long-running loops
        $queues = AssignmentQueue::where('status', 'waiting')
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();

        foreach ($queues as $queue) {
            $engagement = LeadEngagement::find($queue->lead_engagement_id);
            if (!$engagement || $engagement->assigned_user_id !== null) {
                // Someone manually assigned it or it was deleted
                $queue->update(['status' => 'processed', 'failure_reason' => 'Already assigned manually or deleted']);
                continue;
            }

            // Attempt to assign
            $assigned = $engine->autoAssign($engagement, null);

            if ($assigned) {
                $queue->update(['status' => 'processed']);
            }
            // If still unassigned, it remains in 'waiting' status
            // A more advanced system might increment retry_count and flag as 'failed' after X retries
        }
    }
}
