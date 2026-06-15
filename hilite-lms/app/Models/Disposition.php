<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disposition extends Model
{
    protected $fillable = ['company_id', 'stage_id', 'label'];

    public function company() { return $this->belongsTo(Company::class); }
    public function stage()   { return $this->belongsTo(PipelineStage::class); }
}
