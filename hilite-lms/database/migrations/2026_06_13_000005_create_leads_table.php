<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('phone_e164', 20)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'dormant', 'closed'])->default('active');
            $table->timestamp('dormant_at')->nullable();
            $table->timestamps();
            $table->index('phone_e164');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
