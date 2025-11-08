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
            $table->string('member_id_number')->unique()->after('id');
            $table->string('username')->unique()->after('member_id_number');
            $table->string('email')->unique()->after('username');
            $table->string('password')->after('email');
            $table->string('role_id', 25)->after('status');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('member_id_number');
            $table->dropColumn('username');
            $table->dropColumn('email');
            $table->dropColumn('password');
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
