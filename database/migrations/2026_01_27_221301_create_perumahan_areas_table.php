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
        Schema::create('perumahan_areas', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // CUID
            $table->string('area_code', 20)->unique();
            $table->string('area_name', 100);
            $table->text('description')->nullable();
            $table->integer('house_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('area_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perumahan_areas');
    }
};
