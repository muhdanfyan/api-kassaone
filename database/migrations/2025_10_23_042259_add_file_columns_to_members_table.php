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
        Schema::table('members', function (Blueprint $table) {
            $table->string('ktp_scan')->nullable()->after('address');
            $table->string('selfie_with_ktp')->nullable()->after('ktp_scan');
            $table->string('nik', 20)->nullable()->after('selfie_with_ktp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['ktp_scan', 'selfie_with_ktp', 'nik']);
        });
    }
};
