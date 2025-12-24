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
        Schema::table('members', function (Blueprint $table) {
            $table->boolean('is_perumahan')
                ->default(false)
                ->after('member_type')
                ->comment('Status: true = Perumahan, false = Non Perumahan');
            
            // Add index for filtering
            $table->index('is_perumahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['is_perumahan']);
            $table->dropColumn('is_perumahan');
        });
    }
};
