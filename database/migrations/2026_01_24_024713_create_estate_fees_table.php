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
        Schema::create('estate_fees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fee_name', 100);
            $table->enum('fee_type', ['monthly', 'yearly', 'one_time']);
            $table->decimal('amount', 12, 2);
            
            $table->enum('applies_to', ['all', 'owners_only', 'tenants_only', 'specific_houses'])->default('all');
            $table->json('specific_houses')->nullable();
            
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            
            $table->timestamps();
            
            $table->index('fee_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_fees');
    }
};
