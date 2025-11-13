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
        // Get existing columns
        $columns = DB::select("SHOW COLUMNS FROM password_reset_requests");
        $columnNames = array_column($columns, 'Field');
        
        // Check if foreign key exists
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'password_reset_requests' 
            AND TABLE_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'password_reset_requests_matched_member_id_foreign'
        ");
        
        Schema::table('password_reset_requests', function (Blueprint $table) use ($foreignKeys) {
            // Drop foreign key first if it exists
            if (!empty($foreignKeys)) {
                $table->dropForeign(['matched_member_id']);
            }
        });
        
        // Delete rows with NULL matched_member_id (invalid data)
        DB::table('password_reset_requests')->whereNull('matched_member_id')->delete();
        
        Schema::table('password_reset_requests', function (Blueprint $table) use ($columnNames) {
            // Remove old multi-field columns only if they exist
            $columnsToRemove = ['username', 'full_name', 'email', 'phone_number', 'matched_fields'];
            $existingColumns = array_intersect($columnsToRemove, $columnNames);
            
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
            
            // Make matched_member_id NOT NULL (NIK is unique, always matches)
            $table->uuid('matched_member_id')->nullable(false)->change();
            
            // Re-add foreign key
            $table->foreign('matched_member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_requests', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['matched_member_id']);
            
            // Restore old columns
            $table->string('username')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->json('matched_fields')->nullable();
            
            // Make matched_member_id nullable again
            $table->uuid('matched_member_id')->nullable()->change();
            
            // Re-add foreign key
            $table->foreign('matched_member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }
};
