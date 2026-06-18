<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->boolean('auto_assign_enabled')->default(true);
            $table->string('strategy')->default('weighted_round_robin');
            $table->string('fallback_action')->default('queue'); // queue, unassigned
            $table->json('strategy_config')->nullable();
            $table->timestamps();
            
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_rules');
    }
};
