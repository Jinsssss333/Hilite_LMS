<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    protected $fillable = ['company_id', 'stage_id', 'sla_days', 'escalate_to_role'];

    public function company() { return $this->belongsTo(Company::class); }
    public function stage()   { return $this->belongsTo(PipelineStage::class); }
}
