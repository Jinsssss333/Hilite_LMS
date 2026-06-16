<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * Log an audit event.
     * Called by LeadIntakeService, StageTransitionService, AssignmentService, ActivityController.
     * Never throws — audit failure must NEVER crash a request.
     */
    public function log(
        int $companyId,
        ?int $engagementId,
        ?int $actorUserId,
        string $action,
        ?array $before = null,
        ?array $after = null
    ): void {
        try {
            AuditLog::create([
                'company_id'    => $companyId,
                'engagement_id' => $engagementId,
                'actor_user_id' => $actorUserId,
                'action'        => $action,
                'before'        => $before,
                'after'         => $after,
            ]);
        } catch (\Throwable $e) {
            \Log::error('AuditLog write failed: ' . $e->getMessage(), [
                'action'        => $action,
                'engagement_id' => $engagementId,
            ]);
        }
    }
}
