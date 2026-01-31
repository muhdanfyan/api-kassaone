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
        Schema::create('perumahan_fee_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fee_code', 50)->unique()->comment('Kode unik: security, cleaning, road_maintenance');
            $table->string('fee_name', 100)->comment('Nama tampilan: Iuran Keamanan, Iuran Kebersihan');
            $table->decimal('amount', 15, 2)->comment('Nominal iuran');
            $table->boolean('is_active')->default(true)->comment('Status aktif/nonaktif');
            $table->text('description')->nullable()->comment('Deskripsi iuran');
            $table->string('icon', 50)->nullable()->comment('Icon untuk UI: shield, trash, construction');
            $table->integer('sort_order')->default(0)->comment('Urutan tampilan');
            $table->timestamps();
            
            // Indexes
            $table->index('fee_code');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perumahan_fee_settings');
    }
};
