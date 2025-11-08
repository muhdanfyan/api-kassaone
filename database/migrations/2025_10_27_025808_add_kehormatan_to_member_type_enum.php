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
        // MySQL doesn't support adding enum values directly, so we need to use ALTER TABLE
        DB::statement("ALTER TABLE members MODIFY COLUMN member_type ENUM('Pendiri', 'Biasa', 'Calon', 'Kehormatan') DEFAULT 'Biasa'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Kehormatan from enum
        DB::statement("ALTER TABLE members MODIFY COLUMN member_type ENUM('Pendiri', 'Biasa', 'Calon') DEFAULT 'Biasa'");
    }
};
