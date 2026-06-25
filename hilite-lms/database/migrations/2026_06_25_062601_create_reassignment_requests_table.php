<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reassignment_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('engagement_id')->constrained('lead_engagements')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_owner_id')->nullable()->constrained('users')->nullOnDelete();

            // Team lead who reviews first
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            // Branch head — only set when escalated
            $table->foreignId('branch_reviewer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('reason');
            $table->boolean('is_cross_team')->default(false);

            // pending → escalated → approved/denied
            $table->enum('status', ['pending', 'escalated', 'approved', 'denied'])->default('pending');

            $table->text('reviewer_notes')->nullable();
            $table->text('branch_notes')->nullable();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('branch_reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['requester_id', 'status']);
            $table->index(['reviewer_id', 'status']);
            $table->index(['branch_reviewer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reassignment_requests');
    }
};
