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
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->string('type', 50)->nullable(); // e.g., Aktiva, Kewajiban
            $table->string('group', 100)->nullable(); // e.g., Aset Lancar, Aset Tetap
            $table->text('description')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('parent_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('members')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('parent_id');
            $table->index('code');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
