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
            $table->string('id', 25)->primary();
            $table->string('shu_distribution_id', 25);
            $table->foreign('shu_distribution_id')->references('id')->on('shu_distributions');
            $table->string('member_id', 25);
            $table->foreign('member_id')->references('id')->on('members');
            $table->decimal('amount_allocated', 15, 2);
            $table->boolean('is_paid_out')->default(false);
            $table->string('payout_transaction_id', 25)->nullable()->unique();
            $table->foreign('payout_transaction_id')->references('id')->on('transactions');
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
