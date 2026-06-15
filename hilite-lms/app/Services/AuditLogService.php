<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * Log an audit event.
     * Dev C will expand this with event broadcasting, notification logic, etc.
     */
    public function log(
        int $companyId,
        ?int $engagementId,
        ?int $actorUserId,
        string $action,
        ?array $before = null,
        ?array $after = null
    ): AuditLog {
        return AuditLog::create([
            'company_id'     => $companyId,
            'engagement_id'  => $engagementId,
            'actor_user_id'  => $actorUserId,
            'action'         => $action,
            'before'         => $before,
            'after'          => $after,
        ]);
    }
}
