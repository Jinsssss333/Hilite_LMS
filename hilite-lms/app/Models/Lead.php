<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['phone_e164', 'name', 'email', 'status', 'dormant_at', 'meta'];

    protected $casts = [
        'dormant_at' => 'datetime',
        'meta' => 'array',
    ];

    public function engagements() { return $this->hasMany(LeadEngagement::class); }
}
