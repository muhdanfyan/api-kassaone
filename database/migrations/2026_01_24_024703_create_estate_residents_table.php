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
        Schema::create('estate_residents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('house_number', 10)->unique();
            $table->string('owner_name', 100);
            $table->string('owner_phone', 20)->nullable();
            $table->string('owner_email', 100)->nullable();
            $table->string('nik', 20)->nullable();
            
            // Current Occupant Info
            $table->string('current_occupant_name', 100)->nullable();
            $table->string('current_occupant_phone', 20)->nullable();
            $table->enum('current_occupant_relationship', ['owner', 'tenant', 'family'])->default('owner');
            
            // House Details
            $table->enum('house_type', ['36', '45', '54', '60', '70', 'custom'])->default('45');
            $table->enum('house_status', ['owner_occupied', 'rented', 'vacant'])->default('owner_occupied');
            
            // Contact
            $table->integer('total_occupants')->default(1);
            $table->boolean('has_vehicle')->default(false);
            $table->integer('vehicle_count')->default(0);
            
            // Status
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('joined_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('house_number');
            $table->index('owner_name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_residents');
    }
};
