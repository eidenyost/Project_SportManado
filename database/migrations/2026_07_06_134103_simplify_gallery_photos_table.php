<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropColumn(['event_id', 'title', 'description', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::table('gallery_photos', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date');
        });
    }
};
