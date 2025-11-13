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
        // Use raw SQL to check and add unique constraints if they don't exist
        $constraints = [
            'members_username_unique' => 'ALTER TABLE members ADD UNIQUE KEY members_username_unique (username)',
            'members_email_unique' => 'ALTER TABLE members ADD UNIQUE KEY members_email_unique (email)',
            'members_nik_unique' => 'ALTER TABLE members ADD UNIQUE KEY members_nik_unique (nik)',
            'members_phone_number_unique' => 'ALTER TABLE members ADD UNIQUE KEY members_phone_number_unique (phone_number)',
        ];
        
        $indexes = [
            'members_nik_index' => 'ALTER TABLE members ADD INDEX members_nik_index (nik)',
            'members_email_index' => 'ALTER TABLE members ADD INDEX members_email_index (email)',
        ];
        
        // Get existing indexes
        $existingIndexes = DB::select("SHOW INDEX FROM members");
        $existingIndexNames = array_column($existingIndexes, 'Key_name');
        
        // Add constraints if they don't exist
        foreach ($constraints as $constraintName => $sql) {
            if (!in_array($constraintName, $existingIndexNames)) {
                try {
                    DB::statement($sql);
                } catch (\Exception $e) {
                    // Skip if already exists
                }
            }
        }
        
        // Add indexes if they don't exist
        foreach ($indexes as $indexName => $sql) {
            if (!in_array($indexName, $existingIndexNames)) {
                try {
                    DB::statement($sql);
                } catch (\Exception $e) {
                    // Skip if already exists
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Drop unique constraints
            $table->dropUnique('members_username_unique');
            $table->dropUnique('members_email_unique');
            $table->dropUnique('members_nik_unique');
            $table->dropUnique('members_phone_number_unique');
            
            // Drop indexes
            $table->dropIndex('members_nik_index');
            $table->dropIndex('members_email_index');
        });
    }
};
