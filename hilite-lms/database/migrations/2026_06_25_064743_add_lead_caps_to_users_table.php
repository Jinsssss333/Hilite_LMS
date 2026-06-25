<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Max concurrent active leads this user can be assigned at once.
            // NULL = unlimited (fallback to system default of 10)
            $table->unsignedSmallInteger('max_lead_cap')->nullable()->after('is_active')
                ->comment('Hard ceiling: system will not auto-assign if user is at or above this count. NULL = use system default.');

            // Min floor — used by managers to flag under-loaded users.
            // Not enforced on auto-assign but shown as a warning in the assignment hub.
            $table->unsignedSmallInteger('min_lead_floor')->nullable()->after('max_lead_cap')
                ->comment('Soft floor: assignment hub highlights users below this threshold. NULL = no floor.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['max_lead_cap', 'min_lead_floor']);
        });
    }
};
