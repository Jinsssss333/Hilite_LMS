<?php

namespace App\Services;

use App\Models\LeadEngagement;
use Illuminate\Support\Carbon;

class LeadScoringService
{
    // Profile Scores
    const SCORE_PROFILE_EMAIL = 10;
    const SCORE_PROFILE_PHONE = 10;
    const SCORE_PROFILE_COMPANY = 10;
    const SCORE_PROFILE_BUDGET = 20;
    const SCORE_PROFILE_DECISION_MAKER = 20;

    // Source Scores
    const SCORE_SOURCE_REFERRAL = 20;
    const SCORE_SOURCE_WEB_DEMO = 20;
    const SCORE_SOURCE_WALK_IN = 15;
    const SCORE_SOURCE_PHONE = 15;
    const SCORE_SOURCE_LINKEDIN = 10;
    const SCORE_SOURCE_FACEBOOK = 5;
    const SCORE_SOURCE_DEFAULT = 0;

    // Freshness Scores
    const SCORE_FRESH_TODAY = 10;
    const SCORE_FRESH_RECENT = 5;
    const PENALTY_STALE = -10;

    // Negative Scores
    const PENALTY_INVALID_PHONE = -30;
    const PENALTY_BOUNCED_EMAIL = -20;
    const PENALTY_INACTIVE = -15;

    // Engagement Scores
    const SCORE_MULTIPLE_ENGAGEMENTS = 30;

    public function __construct(protected AuditLogService $auditService) {}

    /**
     * Calculates the score for the given lead engagement, applies clamps,
     * determines the rating, and saves the updates if the score has changed.
     * Logs to AuditLog if the score changes.
     */
    public function calculate(LeadEngagement $engagement): void
    {
        $oldScore = $engagement->lead_score;
        $oldRating = $engagement->lead_rating;

        // Ensure lead is loaded
        $engagement->loadMissing('lead');
        $lead = $engagement->lead;

        if (!$lead) return;

        $score = 0;
        $score += $this->calculateProfileScore($lead);
        $score += $this->calculateSourceScore($engagement);
        $score += $this->calculateFreshnessScore($engagement);
        $score += $this->calculateNegativeScore($engagement, $lead);
        $score += $this->calculateEngagementScore($engagement, $lead);

        // Clamp values between 0 and 100
        $score = max(0, min(100, $score));

        $rating = $this->getRating($score);

        if ($score !== $oldScore || $rating !== $oldRating) {
            $engagement->update([
                'lead_score' => $score,
                'lead_rating' => $rating,
                'scored_at' => Carbon::now(),
            ]);

            $this->auditService->log(
                companyId: $engagement->company_id,
                engagementId: $engagement->id,
                actorUserId: null, // System action
                action: 'lead_rescored',
                before: ['score' => $oldScore, 'rating' => $oldRating],
                after: ['score' => $score, 'rating' => $rating],
            );
        }
    }

    protected function calculateProfileScore($lead): int
    {
        $score = 0;
        
        if (!empty($lead->email)) $score += self::SCORE_PROFILE_EMAIL;
        if (!empty($lead->phone_e164)) $score += self::SCORE_PROFILE_PHONE;
        
        $meta = is_array($lead->meta) ? $lead->meta : (json_decode($lead->meta ?? '{}', true) ?: []);

        if (!empty($meta['company'])) $score += self::SCORE_PROFILE_COMPANY;
        if (!empty($meta['budget'])) $score += self::SCORE_PROFILE_BUDGET;
        if (!empty($meta['is_decision_maker'])) $score += self::SCORE_PROFILE_DECISION_MAKER;

        return $score;
    }

    protected function calculateSourceScore(LeadEngagement $engagement): int
    {
        $source = strtolower($engagement->source ?? '');
        
        // Exact mappings based on V1 Manual Scoring Rules
        if ($source === 'referral') return self::SCORE_SOURCE_REFERRAL;
        if ($source === 'website demo' || $source === 'webhook') return self::SCORE_SOURCE_WEB_DEMO; 
        if ($source === 'walk in') return self::SCORE_SOURCE_WALK_IN;
        if ($source === 'phone inquiry' || $source === 'callsync_auto') return self::SCORE_SOURCE_PHONE;
        if ($source === 'linkedin') return self::SCORE_SOURCE_LINKEDIN;
        if ($source === 'facebook') return self::SCORE_SOURCE_FACEBOOK;
        if ($source === 'imported csv' || $source === 'csv') return self::SCORE_SOURCE_DEFAULT;
        
        // Fallback for manual or generic
        return self::SCORE_SOURCE_DEFAULT;
    }

    protected function calculateFreshnessScore(LeadEngagement $engagement): int
    {
        // Use last_activity_at or created_at for freshness
        $date = $engagement->created_at ?? Carbon::now();
        
        // Ensure both are in the same timezone for comparison
        $daysOld = Carbon::parse($date)->startOfDay()->diffInDays(Carbon::now()->startOfDay());

        if ($daysOld == 0) {
            return self::SCORE_FRESH_TODAY;
        } elseif ($daysOld <= 3) {
            return self::SCORE_FRESH_RECENT;
        } elseif ($daysOld > 30) {
            return self::PENALTY_STALE;
        }
        
        return 0;
    }

    protected function calculateNegativeScore(LeadEngagement $engagement, $lead): int
    {
        $score = 0;

        // Invalid phone (assume check via meta or if length is too short)
        $meta = is_array($lead->meta) ? $lead->meta : (json_decode($lead->meta ?? '{}', true) ?: []);
        if (!empty($meta['invalid_phone'])) {
            $score += self::PENALTY_INVALID_PHONE;
        }

        // Email bounced
        if (!empty($meta['email_bounced'])) {
            $score += self::PENALTY_BOUNCED_EMAIL;
        }

        // Inactive for 30 days
        $lastActivity = $engagement->last_activity_at ?? $engagement->created_at;
        if ($lastActivity && $lastActivity->diffInDays(Carbon::now()) > 30) {
            $score += self::PENALTY_INACTIVE;
        }

        return $score;
    }

    protected function calculateEngagementScore(LeadEngagement $engagement, $lead): int
    {
        $score = 0;
        // Repeated interest shows they are "hot". Give +30 for multiple engagements
        $duplicateCount = LeadEngagement::withoutGlobalScopes()
            ->where('company_id', $engagement->company_id)
            ->where('lead_id', $lead->id)
            ->where('id', '!=', $engagement->id)
            ->count();
        if ($duplicateCount > 0) {
            $score += self::SCORE_MULTIPLE_ENGAGEMENTS;
        }
        
        return $score;
    }

    public function getRating(int $score): string
    {
        if ($score <= 39) return 'Cold';
        if ($score <= 59) return 'Warm';
        if ($score <= 79) return 'Hot';
        return 'Very Hot';
    }
}
