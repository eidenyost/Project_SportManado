<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('ktp_path')->nullable()->after('logo');
            $table->string('selfie_path')->nullable()->after('ktp_path');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn(['ktp_path', 'selfie_path']);
        });
    }
};
