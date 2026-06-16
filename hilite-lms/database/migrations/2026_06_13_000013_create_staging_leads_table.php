<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staging_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_job_id')->nullable()->constrained('import_jobs')->nullOnDelete();
            $table->string('raw_phone');
            $table->string('raw_name');
            $table->string('raw_email')->nullable();
            $table->string('source')->default('manual');
            $table->text('raw_notes')->nullable();
            $table->json('meta')->nullable();
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();
            $table->index(['status', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staging_leads');
    }
};
