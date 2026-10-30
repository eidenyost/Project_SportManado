<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->string('transfer_proof_path')->nullable();
            $table->string('transfer_reference',100)->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['transfer_proof_path','transfer_reference','transferred_at','approved_by']);
        });
    }
};
