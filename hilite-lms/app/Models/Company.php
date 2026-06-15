<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'slug', 'webhook_key', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function users()    { return $this->hasMany(User::class); }
    public function branches() { return $this->hasMany(Branch::class); }
    public function teams()    { return $this->hasMany(Team::class); }
    public function stages()   { return $this->hasMany(PipelineStage::class); }
    public function engagements() { return $this->hasMany(LeadEngagement::class); }
}
