<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoutingPolicy extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'mode',
        'scope_level',
        'targets',
        'round_robin_cursor',
    ];

    protected $casts = [
        'targets' => 'array',
    ];
}
