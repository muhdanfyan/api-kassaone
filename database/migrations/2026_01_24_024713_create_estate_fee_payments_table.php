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
        Schema::create('estate_fee_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payment_number', 20)->unique();
            
            $table->uuid('resident_id');
            $table->string('house_number', 10);
            
            $table->uuid('fee_id');
            $table->integer('period_month');
            $table->integer('period_year');
            
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'transfer', 'qris', 'other'])->default('cash');
            
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('due_date')->nullable();
            
            $table->integer('late_days')->default(0);
            $table->decimal('penalty_amount', 10, 2)->default(0);
            
            $table->string('receipt_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('proof_url', 255)->nullable();
            
            $table->uuid('recorded_by')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('resident_id')->references('id')->on('estate_residents')->onDelete('cascade');
            $table->foreign('fee_id')->references('id')->on('estate_fees')->onDelete('restrict');
            $table->index('payment_number');
            $table->index(['resident_id', 'house_number']);
            $table->index(['period_year', 'period_month']);
            $table->index('status');
            $table->index('due_date');
            $table->unique(['resident_id', 'fee_id', 'period_year', 'period_month'], 'unique_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estate_fee_payments');
    }
};
