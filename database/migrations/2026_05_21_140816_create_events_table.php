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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->dateTime('event_date');
            $table->dateTime('registration_deadline');
            $table->integer('max_participants');
            $table->decimal('ticket_price', 10, 2)->default(0);
            $table->string('category');
            $table->string('banner')->nullable();
            $table->string('proposal')->nullable();
            $table->string('permit')->nullable();
            $table->string('ktp_image')->nullable();
            $table->enum('status', ['draft', 'pending', 'active', 'cancelled'])
                ->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
