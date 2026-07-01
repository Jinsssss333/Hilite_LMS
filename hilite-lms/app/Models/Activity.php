<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'engagement_id', 'created_by_user_id', 'disposition_id',
        'type', 'notes', 'follow_up_at', 'occurred_at'
    ];

    protected $casts = ['follow_up_at' => 'datetime'];

    public function engagement()  { return $this->belongsTo(LeadEngagement::class, 'engagement_id'); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function disposition() { return $this->belongsTo(Disposition::class); }
}
