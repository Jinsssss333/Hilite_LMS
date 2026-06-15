<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    protected $fillable = ['company_id', 'name', 'order', 'color', 'sla_days', 'is_closed'];

    protected $casts = ['is_closed' => 'boolean'];

    public function company()      { return $this->belongsTo(Company::class); }
    public function engagements()  { return $this->hasMany(LeadEngagement::class, 'stage_id'); }
    public function dispositions() { return $this->hasMany(Disposition::class, 'stage_id'); }
}
