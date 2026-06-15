<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\LeadEngagement;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ActivityController extends Controller
{
    public function __construct(protected AuditLogService $auditService) {}

    /**
     * POST /api/engagements/{id}/activities
     * Logs an activity (note, followup, call, visit) against an engagement.
     */
    public function store(Request $request, int $engagementId)
    {
        $request->validate([
            'type'           => 'required|in:note,followup,call,visit',
            'disposition_id' => 'nullable|integer|exists:dispositions,id',
            'notes'          => 'nullable|string|max:2000',
            'follow_up_at'   => 'nullable|date|after:now',
        ]);

        // LeadEngagement global scope applies — already company-scoped
        $engagement = LeadEngagement::findOrFail($engagementId);

        // Ownership check: only assigned exec OR privileged roles
        $user = $request->user();
        $allowedRoles = ['team_lead', 'manager', 'branch_head', 'admin', 'super_admin'];
        $isOwner = $engagement->assigned_user_id === $user->id;
        $isPrivileged = in_array($user->role, $allowedRoles);

        if (!$isOwner && !$isPrivileged) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this lead.',
                'errors'  => []
            ], 403);
        }

        $activity = Activity::create([
            'engagement_id'      => $engagement->id,
            'created_by_user_id' => $user->id,
            'disposition_id'     => $request->disposition_id,
            'type'               => $request->type,
            'notes'              => $request->notes,
            'follow_up_at'       => $request->follow_up_at,
        ]);

        // Always update last_activity_at on the engagement
        $engagement->update(['last_activity_at' => Carbon::now()]);

        $this->auditService->log(
            companyId: $engagement->company_id,
            engagementId: $engagement->id,
            actorUserId: $user->id,
            action: 'activity_logged',
            before: null,
            after: ['type' => $activity->type, 'notes' => $activity->notes]
        );

        $activity->load(['createdBy', 'disposition']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $activity->id,
                'engagement_id' => $activity->engagement_id,
                'type'          => $activity->type,
                'disposition'   => $activity->disposition ? ['id' => $activity->disposition->id, 'label' => $activity->disposition->label] : null,
                'notes'         => $activity->notes,
                'follow_up_at'  => $activity->follow_up_at?->toIso8601String(),
                'created_by'    => ['id' => $activity->createdBy->id, 'name' => $activity->createdBy->name],
                'created_at'    => $activity->created_at->toIso8601String(),
            ],
            'message' => 'Activity logged',
        ], 201);
    }

    /**
     * GET /api/activities/upcoming
     * Returns upcoming follow-ups for the authenticated user within N days.
     */
    public function upcoming(Request $request)
    {
        $request->validate(['days' => 'nullable|integer|min:1|max:30']);
        $days      = $request->days ?? 7;
        $userId    = $request->user()->id;
        $companyId = app('current_company_id');

        // Activities where follow_up_at is within N days
        // Join through engagement to enforce company scope
        $activities = Activity::with(['engagement.lead', 'engagement.stage'])
            ->where('type', 'followup')
            ->whereNotNull('follow_up_at')
            ->whereBetween('follow_up_at', [Carbon::now(), Carbon::now()->addDays($days)])
            ->whereHas('engagement', fn($q) =>
                $q->where('company_id', $companyId)
                  ->where('assigned_user_id', $userId)
            )
            ->orderBy('follow_up_at')
            ->get()
            ->map(fn($a) => [
                'activity_id'   => $a->id,
                'engagement_id' => $a->engagement_id,
                'lead_name'     => $a->engagement->lead->name,
                'phone_e164'    => $a->engagement->lead->phone_e164,
                'type'          => $a->type,
                'notes'         => $a->notes,
                'follow_up_at'  => $a->follow_up_at->toIso8601String(),
                'stage'         => $a->engagement->stage->name,
                'overdue'       => $a->follow_up_at->isPast(),
            ]);

        return response()->json(['success' => true, 'data' => $activities]);
    }
}
