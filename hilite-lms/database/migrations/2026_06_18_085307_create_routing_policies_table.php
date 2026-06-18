<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routing_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mode'); // fixed, round_robin, capacity
            $table->string('scope_level')->nullable(); // branch, team, sp
            $table->json('targets')->nullable(); // JSON array of targets {target_type, target_id, weight}
            $table->integer('round_robin_cursor')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_policies');
    }
};
