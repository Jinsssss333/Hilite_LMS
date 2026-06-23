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
            $table->integer('lead_score')->default(0);
            $table->string('lead_rating')->nullable();
            $table->timestamp('scored_at')->nullable();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->json('meta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_engagements', function (Blueprint $table) {
            $table->dropColumn(['lead_score', 'lead_rating', 'scored_at']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
