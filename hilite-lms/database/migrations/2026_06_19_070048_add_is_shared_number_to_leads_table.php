<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX #8a — Generic/Shared Phone Number Pollution
 *
 * Adds is_shared_number boolean to the leads table.
 *
 * When TRUE, the LeadIntakeService bypasses normal phone-based deduplication,
 * allowing multiple unique people who share the same corporate/switchboard
 * phone number to each be treated as separate leads with separate engagements.
 *
 * This flag is set via the API: PATCH /api/leads/{id}/flag-shared
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('is_shared_number')->default(false)->after('status');
            $table->index('is_shared_number');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['is_shared_number']);
            $table->dropColumn('is_shared_number');
        });
    }
};
