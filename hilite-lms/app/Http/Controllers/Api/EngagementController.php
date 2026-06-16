<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadEngagement;
use App\Services\StageTransitionService;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    public function __construct(protected StageTransitionService $stageService) {}

    /**
     * PATCH /api/engagements/{id}/stage
     * Moves an engagement to a new pipeline stage.
     */
    public function updateStage(Request $request, int $engagementId)
    {
        $request->validate([
            'stage_id'       => 'required|integer|exists:pipeline_stages,id',
            'disposition_id' => 'nullable|integer|exists:dispositions,id',
            'notes'          => 'nullable|string|max:2000',
        ]);

        // LeadEngagement has global company scope — this findOrFail is already scoped
        $engagement = LeadEngagement::findOrFail($engagementId);

        try {
            $updated = $this->stageService->transition(
                engagement:    $engagement,
                newStageId:    $request->stage_id,
                actorUserId:   $request->user()->id,
                dispositionId: $request->disposition_id,
                notes:         $request->notes,
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'engagement_id'  => $updated->id,
                'previous_stage' => ['id' => $engagement->stage_id, 'name' => $engagement->stage->name],
                'current_stage'  => ['id' => $updated->stage->id, 'name' => $updated->stage->name],
                'sla_due_at'     => $updated->sla_due_at?->toIso8601String(),
            ],
            'message' => 'Stage updated',
        ]);
    }
}
