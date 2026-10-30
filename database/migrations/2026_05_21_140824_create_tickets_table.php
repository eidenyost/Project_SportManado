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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('ticket_code')->unique();
            $table->string('qr_code_path')->nullable();
            $table->enum('status', ['pending', 'active', 'used', 'cancelled'])
                ->default('pending');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Satu user hanya boleh 1 tiket per event
            $table->unique(['user_id', 'event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
