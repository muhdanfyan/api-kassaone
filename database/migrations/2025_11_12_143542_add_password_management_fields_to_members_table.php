<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('temporary_password')->nullable()->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('temporary_password');
            $table->boolean('is_admin_created')->default(false)->after('password_changed_at');
        });
        
        // Update verification_status enum to include 'pending_documents'
        DB::statement("ALTER TABLE members MODIFY COLUMN verification_status ENUM('pending', 'pending_documents', 'payment_pending', 'verified', 'rejected') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['temporary_password', 'password_changed_at', 'is_admin_created']);
        });
        
        // Revert verification_status enum
        DB::statement("ALTER TABLE members MODIFY COLUMN verification_status ENUM('pending', 'payment_pending', 'verified', 'rejected') DEFAULT 'pending'");
    }
};
