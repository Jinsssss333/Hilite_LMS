<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReassignmentRequest extends Model
{
    protected $fillable = [
        'engagement_id',
        'requester_id',
        'current_owner_id',
        'reviewer_id',
        'branch_reviewer_id',
        'reason',
        'is_cross_team',
        'status',
        'reviewer_notes',
        'branch_notes',
        'reviewed_at',
        'branch_reviewed_at',
    ];

    protected $casts = [
        'is_cross_team'      => 'boolean',
        'reviewed_at'        => 'datetime',
        'branch_reviewed_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function engagement()    { return $this->belongsTo(LeadEngagement::class); }
    public function requester()     { return $this->belongsTo(User::class, 'requester_id'); }
    public function currentOwner()  { return $this->belongsTo(User::class, 'current_owner_id'); }
    public function reviewer()      { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function branchReviewer(){ return $this->belongsTo(User::class, 'branch_reviewer_id'); }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending($q)              { return $q->where('status', 'pending'); }
    public function scopeEscalated($q)            { return $q->where('status', 'escalated'); }
    public function scopeForReviewer($q, $userId) { return $q->where('reviewer_id', $userId); }
    public function scopeForBranchReviewer($q, $userId) { return $q->where('branch_reviewer_id', $userId); }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending()   { return $this->status === 'pending'; }
    public function isEscalated() { return $this->status === 'escalated'; }
    public function isApproved()  { return $this->status === 'approved'; }
    public function isDenied()    { return $this->status === 'denied'; }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'Under Review',
            'escalated' => 'Escalated',
            'approved'  => 'Approved',
            'denied'    => 'Denied',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'   => 'text-amber-600 bg-amber-50 border-amber-200',
            'escalated' => 'text-purple-600 bg-purple-50 border-purple-200',
            'approved'  => 'text-green-700 bg-green-50 border-green-200',
            'denied'    => 'text-red-600 bg-red-50 border-red-200',
        };
    }
}
