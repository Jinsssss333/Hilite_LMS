<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['phone_e164', 'name', 'email', 'status', 'dormant_at', 'is_shared_number'];

    protected $casts = [
        'dormant_at'       => 'datetime',
        'is_shared_number' => 'boolean',
    ];

    public function engagements() { return $this->hasMany(LeadEngagement::class); }
}
