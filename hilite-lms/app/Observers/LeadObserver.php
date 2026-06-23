<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\LeadScoringService;

class LeadObserver
{
    public function __construct(protected LeadScoringService $scoringService) {}

    public function updated(Lead $lead): void
    {
        // Only recalculate if scoring-relevant fields changed
        if ($lead->wasChanged(['email', 'phone_e164', 'meta'])) {
            // Find all active engagements for this lead and rescore them
            $activeEngagements = $lead->engagements()
                ->where('status', 'active')
                ->with('lead')
                ->get();
                
            foreach ($activeEngagements as $engagement) {
                $this->scoringService->calculate($engagement);
            }
        }
    }
}
