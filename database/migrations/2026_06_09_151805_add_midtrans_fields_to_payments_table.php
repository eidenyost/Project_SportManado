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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('snap_token')->nullable()->after('proof_image');
            $table->string('midtrans_transaction_id')->nullable()->after('snap_token');
            $table->string('payment_type')->nullable()->after('method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['snap_token', 'midtrans_transaction_id', 'payment_type']);
        });
    }
};
