<?php

namespace App\Services\Routing;

use App\Models\LeadEngagement;
use App\Models\User;
use App\Models\AssignmentRule;
use App\Models\AssignmentQueue;
use App\Services\AssignmentService;
use Illuminate\Support\Facades\DB;

class AssignmentEngine
{
    public function __construct(protected AssignmentService $assignmentService) {}

    /**
     * Automatically assigns a lead based on company rules.
     * 
     * @param LeadEngagement $engagement
     * @param int|null $actorUserId (Optional creator of the lead, for audit log)
     * @return bool True if assigned, False if queued or unassigned
     */
    public function autoAssign(LeadEngagement $engagement, ?int $actorUserId = null): bool
    {
        $rule = AssignmentRule::firstOrCreate(
            ['company_id' => $engagement->company_id],
            ['auto_assign_enabled' => true, 'strategy' => 'weighted_round_robin', 'fallback_action' => 'queue']
        );

        if (!$rule->auto_assign_enabled) {
            return false;
        }

        if ($rule->strategy === 'weighted_round_robin') {
            return $this->weightedRoundRobin($engagement, $rule, $actorUserId);
        }

        return false;
    }

    protected function weightedRoundRobin(LeadEngagement $engagement, AssignmentRule $rule, ?int $actorUserId): bool
    {
        // 1. Fetch eligible salespeople
        // For phase 1, we consider all active, available salespeople in the company.
        $users = User::where('company_id', $engagement->company_id)
            ->where('is_active', true)
            ->where('is_available', true)
            ->where('role', 'salesperson')
            ->withCount(['engagements as active_leads_count' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get();

        if ($users->isEmpty()) {
            return $this->handleFallback($engagement, $rule);
        }

        $bestUser = null;
        $bestScore = PHP_FLOAT_MAX;

        foreach ($users as $user) {
            // Check capacity
            $capacity = $user->max_active_leads > 0 ? $user->max_active_leads : 50;
            if ($user->active_leads_count >= $capacity) {
                continue; // Skip, at capacity
            }

            // Base score: load percentage
            $score = $user->active_leads_count / $capacity;

            // In Phase 2: Multiply by skill/territory bonuses here.
            // Example: $score *= $territoryMatch ? 0.8 : 1.5;

            // Tie-breaker or jitter can be added here
            
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestUser = $user;
            }
        }

        if ($bestUser) {
            $this->assignmentService->assign(
                engagement: $engagement,
                assignToUserId: $bestUser->id,
                actorUserId: $actorUserId,
                reason: 'Auto-assigned via weighted_round_robin'
            );
            return true;
        }

        // Everyone is at capacity
        return $this->handleFallback($engagement, $rule);
    }

    protected function handleFallback(LeadEngagement $engagement, AssignmentRule $rule): bool
    {
        if ($rule->fallback_action === 'queue') {
            AssignmentQueue::firstOrCreate([
                'company_id' => $engagement->company_id,
                'lead_engagement_id' => $engagement->id,
                'status' => 'waiting'
            ]);
        }
        return false;
    }
}
