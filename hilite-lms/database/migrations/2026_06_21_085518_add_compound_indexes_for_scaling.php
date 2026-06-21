<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lead_engagements', function (Blueprint $table) {
            $table->index(['company_id', 'assigned_user_id', 'status'], 'idx_engagements_company_user_status');
            $table->index(['company_id', 'stage_id', 'status'], 'idx_engagements_company_stage_status');
            $table->index(['company_id', 'created_at'], 'idx_engagements_company_created_at');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->index(['engagement_id', 'type', 'occurred_at'], 'idx_activities_eng_type_occurred');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_engagements', function (Blueprint $table) {
            $table->dropIndex('idx_engagements_company_user_status');
            $table->dropIndex('idx_engagements_company_stage_status');
            $table->dropIndex('idx_engagements_company_created_at');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_eng_type_occurred');
        });
    }
};
