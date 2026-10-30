<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE events MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('draft','pending','active','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
