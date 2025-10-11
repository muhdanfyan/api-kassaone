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
        Schema::create('shu_member_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shu_distribution_id')->constrained('shu_distributions');
            $table->foreignId('member_id')->constrained('members');
            $table->decimal('amount_allocated', 15, 2);
            $table->boolean('is_paid_out')->default(false);
            $table->foreignId('payout_transaction_id')->nullable()->unique()->constrained('transactions');
            $table->unique(['shu_distribution_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shu_member_allocations');
    }
};
