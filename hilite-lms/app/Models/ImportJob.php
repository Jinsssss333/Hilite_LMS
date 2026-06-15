<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'uploaded_by_user_id', 'original_filename',
        'status', 'total_rows', 'processed_rows', 'created_count',
        'duplicate_count', 'failed_count', 'error_log'
    ];

    protected $casts = ['error_log' => 'array'];

    public function company()    { return $this->belongsTo(Company::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by_user_id'); }
    public function stagingLeads() { return $this->hasMany(StagingLead::class); }
}
