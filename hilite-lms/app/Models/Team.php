<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = ['company_id', 'name', 'branch_id'];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch()  { return $this->belongsTo(Branch::class); }
    public function users()       { return $this->hasMany(User::class); }
    public function engagements() { return $this->hasMany(LeadEngagement::class, 'assigned_team_id'); }
}
