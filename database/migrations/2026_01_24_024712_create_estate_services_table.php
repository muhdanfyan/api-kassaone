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
        Schema::create('estate_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number', 20)->unique();
            
            $table->uuid('resident_id')->nullable();
            $table->string('house_number', 10);
            $table->string('reporter_name', 100);
            $table->string('reporter_phone', 20)->nullable();
            
            $table->enum('category', ['complaint', 'maintenance', 'facility_booking', 'information', 'emergency']);
            $table->string('sub_category', 50)->nullable();
            $table->string('title', 200);
            $table->text('description');
            $table->string('location', 200)->nullable();
            
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['submitted', 'acknowledged', 'in_progress', 'resolved', 'closed', 'rejected'])->default('submitted');
            
            $table->string('assigned_to', 100)->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            
            $table->json('photos')->nullable();
            
            $table->integer('rating')->nullable();
            $table->text('feedback')->nullable();
            
            $table->timestamps();
            
            $table->foreign('resident_id')->references('id')->on('estate_residents')->onDelete('set null');
            $table->index('ticket_number');
            $table->index('status');
            $table->index('category');
            $table->index('house_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_services');
    }
};
