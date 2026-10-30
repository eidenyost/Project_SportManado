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
        Schema::table('events', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('description');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->json('custom_answers')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('custom_answers');
        });
    }
};
