<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('withdrawal_requests', fn (Blueprint $table) => $table->timestamp('payout_requested_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', fn (Blueprint $table) => $table->dropColumn('payout_requested_at'));
    }
};
