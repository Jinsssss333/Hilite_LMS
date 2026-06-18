<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentRule extends Model
{
    protected $fillable = [
        'company_id', 'auto_assign_enabled', 'strategy', 'fallback_action', 'strategy_config'
    ];

    protected $casts = [
        'auto_assign_enabled' => 'boolean',
        'strategy_config' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
