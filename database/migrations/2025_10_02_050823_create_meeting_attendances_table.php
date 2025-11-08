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
        Schema::create('meeting_attendance', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->string('meeting_id', 25);
            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->string('member_id', 25);
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->boolean('is_present')->default(false);
            $table->unique(['meeting_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
    }
};
