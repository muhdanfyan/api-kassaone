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
        Schema::create('estate_waste_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('schedule_id')->nullable();
            $table->date('collection_date');
            $table->time('collection_time')->nullable();
            
            $table->string('collector_name', 100)->nullable();
            $table->json('houses_collected')->nullable();
            $table->json('houses_skipped')->nullable();
            $table->integer('total_houses')->default(0);
            
            $table->integer('organic_bags')->default(0);
            $table->integer('non_organic_bags')->default(0);
            $table->integer('recyclable_bags')->default(0);
            
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            
            $table->timestamps();
            
            $table->foreign('schedule_id')->references('id')->on('estate_waste_schedules')->onDelete('set null');
            $table->index('collection_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_waste_collections');
    }
};
