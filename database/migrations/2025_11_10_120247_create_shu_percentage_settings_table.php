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
        Schema::create('shu_percentage_settings', function (Blueprint $table) {
            $table->char('id', 25)->primary();
            $table->string('name', 100);
            $table->char('fiscal_year', 4);
            $table->boolean('is_active')->default(false);
            
            // Level 1: Pembagian Total SHU
            $table->decimal('cadangan_percentage', 5, 2)->default(30.00);
            $table->decimal('anggota_percentage', 5, 2)->default(70.00);
            $table->decimal('pengurus_percentage', 5, 2)->default(0.00);
            $table->decimal('karyawan_percentage', 5, 2)->default(0.00);
            $table->decimal('dana_sosial_percentage', 5, 2)->default(0.00);
            
            // Level 2: Pembagian Bagian Anggota
            $table->decimal('jasa_modal_percentage', 5, 2)->default(40.00);
            $table->decimal('jasa_usaha_percentage', 5, 2)->default(60.00);
            
            // Metadata
            $table->text('description')->nullable();
            $table->char('created_by', 25)->nullable();
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('created_by')->references('id')->on('members')->onDelete('set null');
            
            // Indexes
            $table->index(['fiscal_year', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shu_percentage_settings');
    }
};
