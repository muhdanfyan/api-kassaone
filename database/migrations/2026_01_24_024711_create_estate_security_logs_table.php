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
        Schema::create('estate_security_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('log_type', ['entry', 'exit', 'incident', 'patrol']);
            
            // Entry/Exit Details
            $table->uuid('resident_id')->nullable();
            $table->string('house_number', 10)->nullable();
            $table->string('visitor_name', 100)->nullable();
            $table->string('visitor_phone', 20)->nullable();
            $table->text('visitor_purpose')->nullable();
            $table->string('vehicle_plate', 20)->nullable();
            
            // Incident Details
            $table->string('incident_type', 50)->nullable();
            $table->text('incident_description')->nullable();
            $table->enum('incident_severity', ['low', 'medium', 'high'])->nullable();
            $table->enum('incident_status', ['open', 'investigating', 'resolved'])->default('open')->nullable();
            
            // Patrol Details
            $table->string('patrol_area', 50)->nullable();
            $table->text('patrol_notes')->nullable();
            
            // Common Fields
            $table->dateTime('log_datetime');
            $table->string('guard_name', 100)->nullable();
            $table->enum('guard_shift', ['morning', 'afternoon', 'night'])->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_url', 255)->nullable();
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            
            $table->foreign('resident_id')->references('id')->on('estate_residents')->onDelete('set null');
            $table->index('log_type');
            $table->index('log_datetime');
            $table->index('house_number');
            $table->index('incident_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_security_logs');
    }
};
