<?php

namespace App\Observers;

use App\Models\LeadEngagement;
use App\Services\LeadScoringService;

class LeadEngagementObserver
{
    public function __construct(protected LeadScoringService $scoringService) {}

    public function created(LeadEngagement $engagement): void
    {
        // Initial score calculation
        $this->scoringService->calculate($engagement);
    }

    public function updated(LeadEngagement $engagement): void
    {
        // Recalculate if critical fields change
        if ($engagement->wasChanged(['stage_id', 'assigned_user_id', 'source', 'last_activity_at'])) {
            $this->scoringService->calculate($engagement);
        }
    }
}
