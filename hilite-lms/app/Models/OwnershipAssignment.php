<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnershipAssignment extends Model
{
    protected $fillable = [
        'engagement_id', 'level', 'target_id', 'assigned_to_user_id', 'assigned_by_user_id', 'reason', 'assigned_at', 'valid_from', 'valid_to'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function engagement() { return $this->belongsTo(LeadEngagement::class, 'engagement_id'); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to_user_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by_user_id'); }
}
