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
        Schema::create('members', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            // Removed user_id foreign key - members table now handles auth directly
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->date('date_joined');
            $table->enum('member_type', ['Pendiri', 'Biasa', 'Calon', 'Kehormatan'])->default('Biasa');
            $table->enum('status', ['Aktif', 'Tidak Aktif', 'Ditangguhkan'])->default('Aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
