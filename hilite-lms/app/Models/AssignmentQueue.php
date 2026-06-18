<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentQueue extends Model
{
    protected $fillable = [
        'company_id', 'lead_engagement_id', 'status', 'failure_reason'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function engagement()
    {
        return $this->belongsTo(LeadEngagement::class, 'lead_engagement_id');
    }
}
