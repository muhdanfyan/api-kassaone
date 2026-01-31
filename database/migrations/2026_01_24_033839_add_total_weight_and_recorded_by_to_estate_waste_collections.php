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
        Schema::table('estate_waste_collections', function (Blueprint $table) {
            $table->decimal('total_weight', 10, 2)->default(0)->after('recyclable_bags');
            $table->uuid('recorded_by')->nullable()->after('photos');
            
            $table->index('recorded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estate_waste_collections', function (Blueprint $table) {
            $table->dropColumn(['total_weight', 'recorded_by']);
        });
    }
};
