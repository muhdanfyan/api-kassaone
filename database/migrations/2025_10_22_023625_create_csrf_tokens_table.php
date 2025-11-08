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
        Schema::create('csrf_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('member_id', 25);
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['member_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csrf_tokens');
    }
};
