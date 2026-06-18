<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assignment_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_engagement_id')->constrained('lead_engagements')->cascadeOnDelete();
            $table->string('status')->default('waiting'); // waiting, processed, failed
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_queues');
    }
};
