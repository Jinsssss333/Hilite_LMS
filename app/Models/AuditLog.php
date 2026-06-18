<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id','engagement_id','actor_user_id','action','before','after'
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function actor()      { return $this->belongsTo(User::class, 'actor_user_id'); }
    public function engagement() { return $this->belongsTo(LeadEngagement::class, 'engagement_id'); }
}
