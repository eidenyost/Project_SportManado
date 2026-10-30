<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // The previous enum-to-string migration only ran on MySQL.
            $table->string('status', 20)->default('pending')->change();
            $table->dateTime('event_end_at')->nullable();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dateTime('reservation_expires_at')->nullable()->index();
            // Set before requesting Snap: a timeout can still have created a token remotely.
            $table->dateTime('snap_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['reservation_expires_at']);
            $table->dropColumn(['reservation_expires_at', 'snap_requested_at']);
        });
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('event_end_at'));
    }
};
