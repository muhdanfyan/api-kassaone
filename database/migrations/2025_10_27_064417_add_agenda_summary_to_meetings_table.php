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
        Schema::table('meetings', function (Blueprint $table) {
            $table->text('agenda')->nullable()->after('description');
            $table->text('summary')->nullable()->after('agenda');
            $table->enum('status', ['upcoming', 'completed', 'cancelled'])->default('upcoming')->after('summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['agenda', 'summary', 'status']);
        });
    }
};
