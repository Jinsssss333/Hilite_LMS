<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StagingLead extends Model
{
    protected $fillable = [
        'company_id', 'import_job_id', 'raw_phone', 'raw_name', 'raw_email',
        'source', 'raw_notes', 'meta', 'status', 'failure_reason', 'idempotency_key'
    ];

    protected $casts = ['meta' => 'array'];

    public function company()   { return $this->belongsTo(Company::class); }
    public function importJob() { return $this->belongsTo(ImportJob::class); }
}
