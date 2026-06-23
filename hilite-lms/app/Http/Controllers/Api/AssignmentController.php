<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadEngagement;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(protected AssignmentService $assignmentService) {}

    /**
     * PATCH /api/engagements/{id}/assign
     * TL can assign within team. Manager/Admin can assign anywhere in company.
     */
    public function assign(Request $request, int $id)
    {
        $request->validate([
            'assign_to_user_id' => 'required|integer|exists:users,id',
            'reason'            => 'nullable|string|max:500',
        ]);

        // Global company scope applied via middleware — findOrFail is safe
        $engagement = LeadEngagement::findOrFail($id);

        try {
            $assignment = $this->assignmentService->assign(
                engagement:     $engagement,
                assignToUserId: $request->assign_to_user_id,
                actorUserId:    $request->user()->id,
                reason:         $request->reason,
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => [],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'engagement_id' => $engagement->id,
                'assigned_to'   => [
                    'id'   => $assignment->assignedTo->id,
                    'name' => $assignment->assignedTo->name,
                ],
                'assigned_by'   => [
                    'id'   => $assignment->assignedBy->id,
                    'name' => $assignment->assignedBy->name,
                ],
                'reason'      => $assignment->reason,
                'assigned_at' => $assignment->assigned_at->toIso8601String(),
            ],
            'message' => 'Lead assigned successfully',
        ], 200);
    }

    /**
     * GET /api/users/assignable
     * Returns users the authenticated actor is allowed to assign leads to.
     */
    public function assignable(Request $request)
    {
        $actor = $request->user();
        $users = $this->assignmentService->getAssignableUsers($actor);

        return response()->json([
            'success' => true,
            'data'    => $users->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'role'         => $u->role,
                'active_leads' => \App\Models\LeadEngagement::withoutGlobalScopes()
                    ->where('company_id', $actor->company_id)
                    ->where('assigned_user_id', $u->id)
                    ->where('status', 'active')
                    ->count(),
            ])->values(),
        ], 200);
    }

    /**
     * POST /api/admin/assignments/process-queue
     * Admins can manually trigger the auto-assignment queue.
     */
    public function processQueue(Request $request, \App\Services\Routing\AssignmentEngine $assignmentEngine)
    {
        $companyId = app('current_company_id');
        $assignedCount = $assignmentEngine->processQueue($companyId);

        return response()->json([
            'success' => true,
            'message' => 'Queue processed successfully',
            'data'    => [
                'assigned_count' => $assignedCount
            ]
        ], 200);
    }
}
