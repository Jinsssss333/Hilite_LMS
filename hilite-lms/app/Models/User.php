<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_id', 'branch_id', 'team_id', 'name', 'email', 'password', 'role', 'is_active', 'max_active_leads', 'is_available'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'max_active_leads' => 'integer',
        ];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function branch()  { return $this->belongsTo(Branch::class); }
    public function team()    { return $this->belongsTo(Team::class); }
    public function engagements() { return $this->hasMany(LeadEngagement::class, 'assigned_user_id'); }
}
