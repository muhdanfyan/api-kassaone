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
        Schema::table('expenses', function (Blueprint $table) {
            // Add new fields
            $table->decimal('unit_price', 15, 2)->after('description');
            $table->string('unit', 20)->after('unit_price');
            $table->decimal('quantity', 10, 2)->after('unit');
            
            // Rename amount to amount_old temporarily
            $table->renameColumn('amount', 'amount_old');
        });
        
        // Add computed column (not supported in Laravel, so we use raw SQL)
        DB::statement('ALTER TABLE expenses ADD COLUMN amount DECIMAL(15,2) GENERATED ALWAYS AS (unit_price * quantity) STORED AFTER quantity');
        
        // Optionally migrate old data if exists
        DB::statement("UPDATE expenses SET unit_price = amount_old, unit = 'unit', quantity = 1 WHERE unit_price IS NULL");
        
        // Drop old amount column
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('amount_old');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop computed column
        DB::statement('ALTER TABLE expenses DROP COLUMN amount');
        
        Schema::table('expenses', function (Blueprint $table) {
            // Add back regular amount column
            $table->decimal('amount', 15, 2)->after('quantity');
            
            // Drop new fields
            $table->dropColumn(['unit_price', 'unit', 'quantity']);
        });
    }
};
