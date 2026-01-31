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
        Schema::table('estate_waste_schedules', function (Blueprint $table) {
            $table->string('assigned_team_id', 26)->nullable()->after('coverage_area');
            
            // Foreign key constraint
            $table->foreign('assigned_team_id')
                  ->references('id')
                  ->on('perumahan_staff_teams')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estate_waste_schedules', function (Blueprint $table) {
            $table->dropForeign(['assigned_team_id']);
            $table->dropColumn('assigned_team_id');
        });
    }
};
