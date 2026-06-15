<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('pipeline_stages')->cascadeOnDelete();
            $table->integer('sla_days');
            $table->enum('escalate_to_role', ['team_lead', 'manager', 'admin'])->default('team_lead');
            $table->timestamps();
            $table->unique(['company_id', 'stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
