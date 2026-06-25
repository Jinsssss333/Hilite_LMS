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
        'company_id', 'branch_id', 'team_id', 'name', 'email', 'phone', 'password', 'role', 'is_active',
        'max_lead_cap', 'min_lead_floor',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'is_active'      => 'boolean',
            'max_lead_cap'   => 'integer',
            'min_lead_floor' => 'integer',
        ];
    }

    /**
     * System default cap used when the user has no explicit max_lead_cap set.
     */
    const SYSTEM_DEFAULT_CAP = 30;

    /**
     * Returns true if the user's current active lead count is at or above their cap.
     * Used by the intake service to skip overloaded users during auto-assignment.
     */
    public function isAtLeadCapacity(): bool
    {
        $cap = $this->max_lead_cap ?? self::SYSTEM_DEFAULT_CAP;
        $current = \App\Models\LeadEngagement::withoutGlobalScopes()
            ->where('assigned_user_id', $this->id)
            ->where('status', 'active')
            ->count();
        return $current >= $cap;
    }

    /**
     * Returns current active lead count for display.
     */
    public function activeLeadCount(): int
    {
        return \App\Models\LeadEngagement::withoutGlobalScopes()
            ->where('assigned_user_id', $this->id)
            ->where('status', 'active')
            ->count();
    }

    public function company()     { return $this->belongsTo(Company::class); }
    public function branch()      { return $this->belongsTo(Branch::class); }
    public function team()        { return $this->belongsTo(Team::class); }
    public function engagements() { return $this->hasMany(LeadEngagement::class, 'assigned_user_id'); }
}
