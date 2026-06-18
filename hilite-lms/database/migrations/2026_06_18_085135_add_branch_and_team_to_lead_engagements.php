<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_engagements', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_branch_id')->nullable()->after('assigned_user_id');
            $table->unsignedBigInteger('assigned_team_id')->nullable()->after('assigned_branch_id');
            
            $table->foreign('assigned_branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('assigned_team_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lead_engagements', function (Blueprint $table) {
            $table->dropForeign(['assigned_branch_id']);
            $table->dropForeign(['assigned_team_id']);
            $table->dropColumn(['assigned_branch_id', 'assigned_team_id']);
        });
    }
};
