<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE lead_engagements MODIFY source VARCHAR(255) DEFAULT 'manual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Notice: This down migration might fail if there are strings not in the original enum
        DB::statement("ALTER TABLE lead_engagements MODIFY source ENUM('manual', 'csv', 'webhook', 'callsync_auto') DEFAULT 'manual'");
    }
};
