<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('stage_id')->constrained('pipeline_stages');
            $table->enum('source', ['manual', 'csv', 'webhook', 'callsync_auto'])->default('manual');
            $table->enum('status', ['active', 'dormant', 'closed'])->default('active');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->timestamps();
            $table->unique(['company_id', 'lead_id']);
            $table->index(['company_id', 'stage_id']);
            $table->index(['company_id', 'assigned_user_id']);
            $table->index(['company_id', 'status']);
            $table->index('sla_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_engagements');
    }
};
