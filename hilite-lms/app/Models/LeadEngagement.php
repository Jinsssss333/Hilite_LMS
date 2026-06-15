<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LeadEngagement extends Model
{
    protected $fillable = [
        'company_id','lead_id','assigned_user_id','stage_id',
        'source','status','last_activity_at','sla_due_at','sla_breached'
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'sla_breached' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Automatically scope every query to current company
        // Use withoutGlobalScopes() ONLY in LeadIntakeService for dedup check
        static::addGlobalScope('company', function (Builder $builder) {
            if (app()->bound('current_company_id')) {
                $builder->where('lead_engagements.company_id', app('current_company_id'));
            }
        });
    }

    public function lead()        { return $this->belongsTo(Lead::class); }
    public function stage()       { return $this->belongsTo(PipelineStage::class); }
    public function assignedTo()  { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function activities()  { return $this->hasMany(Activity::class, 'engagement_id'); }
    public function assignments() { return $this->hasMany(OwnershipAssignment::class, 'engagement_id'); }
    public function company()     { return $this->belongsTo(Company::class); }
}
