<?php

namespace App\Services;

use App\Models\LeadEngagement;
use App\Models\PipelineStage;
use App\Models\SlaPolicy;
use App\Services\AuditLogService;
use Illuminate\Support\Carbon;

class StageTransitionService
{
    public function __construct(protected AuditLogService $auditService) {}

    /**
     * Moves an engagement to a new stage.
     * Validates ownership. Sets SLA due date from policy or stage default
     * ONLY on forward progress (new stage order > previous stage order).
     * Logs audit entry.
     *
     * @throws \Exception if user does not own this engagement
     */
    public function transition(
        LeadEngagement $engagement,
        int $newStageId,
        int $actorUserId,
        ?int $dispositionId = null,
        ?string $notes = null
    ): LeadEngagement {
        // Ownership check: only assigned exec OR TL/Manager/Admin can move stage
        $user = \App\Models\User::find($actorUserId);
        $allowedRoles = ['team_lead', 'manager', 'branch_head', 'admin', 'super_admin'];
        $isOwner = $engagement->assigned_user_id === $actorUserId;
        $isPrivileged = in_array($user->role, $allowedRoles);

        if (!$isOwner && !$isPrivileged) {
            throw new \Exception('You do not own this lead. Contact your Team Lead.');
        }

        $newStage      = PipelineStage::findOrFail($newStageId);
        $previousStage = $engagement->stage;
        $isForwardMove = $newStage->order > $previousStage->order;

        $updates = [
            'stage_id'         => $newStageId,
            'last_activity_at' => Carbon::now(),
            'status'           => $newStage->is_closed ? 'closed' : 'active',
        ];

        if ($isForwardMove) {
            // SECURITY: only recalculate/clear SLA on genuine forward progress.
            $slaPolicy = SlaPolicy::where('company_id', $engagement->company_id)
                ->where('stage_id', $newStageId)
                ->first();

            $slaDays = $slaPolicy?->sla_days ?? $newStage->sla_days;
            $updates['sla_due_at']   = $slaDays ? Carbon::now()->addDays($slaDays) : null;
            $updates['sla_breached'] = false;
        }
        // else: lateral/backward move — sla_due_at and sla_breached are left
        // exactly as they were. A breached lead stays breached until the
        // exec actually moves it forward, or TL/manager intervenes.

        $engagement->update($updates);

        if ($newStage->is_closed) {
            // A slot just opened up for this salesperson. Attempt to process queued leads.
            app(\App\Services\Routing\AssignmentEngine::class)->processQueue($engagement->company_id);
        }

        // Log the stage change activity automatically
        if ($dispositionId || $notes) {
            $engagement->activities()->create([
                'created_by_user_id' => $actorUserId,
                'disposition_id'     => $dispositionId,
                'type'               => 'note',
                'notes'              => $notes,
                'follow_up_at'       => null,
            ]);
        }

        $this->auditService->log(
            companyId: $engagement->company_id,
            engagementId: $engagement->id,
            actorUserId: $actorUserId,
            action: 'stage_changed',
            before: ['stage' => $previousStage->name, 'stage_id' => $previousStage->id, 'order' => $previousStage->order],
            after:  ['stage' => $newStage->name,      'stage_id' => $newStage->id,      'order' => $newStage->order, 'forward' => $isForwardMove]
        );

        return $engagement->fresh(['stage']);
    }
}
