<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ownership_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained('lead_engagements')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->constrained('users');
            $table->foreignId('assigned_by_user_id')->constrained('users');
            $table->string('reason')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamps();
            $table->index('engagement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_assignments');
    }
};
