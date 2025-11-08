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
            // Verification Status: pending -> payment_pending -> verified / rejected
            $table->enum('verification_status', ['pending', 'payment_pending', 'verified', 'rejected'])
                ->default('pending')
                ->after('status');
            
            // Payment Information
            $table->decimal('payment_amount', 15, 2)->default(1000000.00)->after('verification_status');
            $table->string('payment_proof')->nullable()->after('payment_amount'); // Path to uploaded proof
            $table->timestamp('payment_uploaded_at')->nullable()->after('payment_proof');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_uploaded_at');
            $table->string('payment_verified_by', 25)->nullable()->after('payment_verified_at'); // Admin ID
            
            // Rejection Information
            $table->text('rejected_reason')->nullable()->after('payment_verified_by');
            $table->timestamp('rejected_at')->nullable()->after('rejected_reason');
            $table->string('rejected_by', 25)->nullable()->after('rejected_at'); // Admin ID
            
            // Foreign keys for admin actions
            $table->foreign('payment_verified_by')->references('id')->on('members')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('members')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by']);
            $table->dropForeign(['rejected_by']);
            
            $table->dropColumn([
                'verification_status',
                'payment_amount',
                'payment_proof',
                'payment_uploaded_at',
                'payment_verified_at',
                'payment_verified_by',
                'rejected_reason',
                'rejected_at',
                'rejected_by',
            ]);
        });
    }
};
