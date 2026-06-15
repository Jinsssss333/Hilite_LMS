<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['company_id', 'name'];

    public function company() { return $this->belongsTo(Company::class); }
    public function teams()   { return $this->hasMany(Team::class); }
    public function users()   { return $this->hasMany(User::class); }
}
