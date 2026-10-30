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
        Schema::create('event_registration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Organizer / Community Info
            $table->string('organization_name');
            $table->string('phone', 30);

            // Event Info
            $table->string('event_title');
            $table->text('event_description');
            $table->string('event_location');
            $table->dateTime('event_date');
            $table->dateTime('registration_deadline');
            $table->integer('max_participants');
            $table->decimal('ticket_price', 10, 2)->default(0);
            $table->string('category');

            // Mandatory documents for admin review
            $table->string('proposal_file');     // Proposal Resmi Acara (required)
            $table->string('permit_file');        // Surat Izin Penyelenggaraan (required)

            // Optional files
            $table->string('banner')->nullable(); // Event banner image

            // Admin review
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registration_requests');
    }
};
