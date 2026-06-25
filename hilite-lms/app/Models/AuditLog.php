<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id', 'engagement_id', 'actor_user_id', 'action', 'before', 'after'
    ];

    protected $casts = ['before' => 'array', 'after' => 'array'];

    public function company()    { return $this->belongsTo(Company::class); }
    public function engagement() { return $this->belongsTo(LeadEngagement::class, 'engagement_id'); }
    public function actor()      { return $this->belongsTo(User::class, 'actor_user_id'); }

    /**
     * Alias for actor() — AdminController uses $log->user in exportAudit().
     */
    public function user()       { return $this->belongsTo(User::class, 'actor_user_id'); }

    /**
     * Static helper for AdminController calls:
     * AuditLog::log($companyId, $actorId, $entityType, $entityId, $action, $description)
     * Maps to actual table columns: company_id, actor_user_id, action, after (stores context).
     */
    public static function log(
        int $companyId,
        int $actorUserId,
        string $entityType,
        int $entityId,
        string $action,
        string $description = ''
    ): void {
        try {
            static::create([
                'company_id'    => $companyId,
                'actor_user_id' => $actorUserId,
                'action'        => $action,
                'after'         => [
                    'entity_type' => $entityType,
                    'entity_id'   => $entityId,
                    'description' => $description,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('AuditLog::log() failed: ' . $e->getMessage());
        }
    }
}
