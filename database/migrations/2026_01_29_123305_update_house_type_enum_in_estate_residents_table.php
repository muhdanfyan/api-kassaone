<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to use raw SQL to modify ENUM
        DB::statement("ALTER TABLE estate_residents MODIFY COLUMN house_type ENUM('36', '45', '54', '60', '70', 'custom') NOT NULL DEFAULT '45'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback to previous enum values
        DB::statement("ALTER TABLE estate_residents MODIFY COLUMN house_type ENUM('36', '45', '60', '70', 'custom') NOT NULL DEFAULT '45'");
    }
};
