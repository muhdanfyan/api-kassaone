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
        Schema::create('password_reset_requests', function (Blueprint $table) {
            $table->char('id', 25)->primary();
            $table->string('username')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->char('matched_member_id', 25)->nullable();
            $table->json('matched_fields')->nullable(); // ["email", "username"]
            $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');
            $table->char('processed_by', 25)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('matched_member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('members')->onDelete('set null');
            
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
