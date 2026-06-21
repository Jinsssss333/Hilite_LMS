<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Add phone column to users table.
 *
 * Stores the E.164 normalized phone number for each user.
 * Normalized via PhoneNormalizationService (giggsey/libphonenumber-for-php)
 * before saving, not raw input.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // E.164 max length is 15 digits + '+' = 16 chars, give a little headroom
            $table->string('phone', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
