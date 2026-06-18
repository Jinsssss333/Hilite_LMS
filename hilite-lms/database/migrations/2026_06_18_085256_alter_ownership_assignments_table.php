<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ownership_assignments', function (Blueprint $table) {
            $table->string('level')->nullable()->after('engagement_id'); // branch, team, sp
            $table->unsignedBigInteger('target_id')->nullable()->after('level'); // ID of the branch/team/sp
            $table->timestamp('valid_from')->nullable()->after('assigned_at');
            $table->timestamp('valid_to')->nullable()->after('valid_from');
            
            // Note: assigned_to_user_id and assigned_by_user_id remain for compatibility
        });
    }

    public function down(): void
    {
        Schema::table('ownership_assignments', function (Blueprint $table) {
            $table->dropColumn(['level', 'target_id', 'valid_from', 'valid_to']);
        });
    }
};
