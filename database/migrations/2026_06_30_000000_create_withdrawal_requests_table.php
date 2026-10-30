<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('restrict');
            $table->foreignId('organizer_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_holder');
            $table->string('status')->default('pending'); // pending, approved, rejected, failed
            $table->string('iris_reference_no')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
