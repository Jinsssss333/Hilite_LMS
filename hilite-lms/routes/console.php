<?php

use App\Jobs\CheckSlaBreachJob;
use App\Jobs\MarkDormantLeadsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Check for SLA breaches on inbound replies (The Outbound Loop Trap)
Schedule::command('sla:check-replies')->hourly();

// Dev C scheduled jobs
Schedule::job(new CheckSlaBreachJob)->hourly();
Schedule::job(new MarkDormantLeadsJob)->weekly();
