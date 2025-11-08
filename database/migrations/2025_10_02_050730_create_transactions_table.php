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
        Schema::create('transactions', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->string('savings_account_id', 25);
            $table->foreign('savings_account_id')->references('id')->on('savings_accounts');
            $table->string('member_id', 25);
            $table->foreign('member_id')->references('id')->on('members');
            $table->enum('transaction_type', ['deposit', 'withdrawal', 'shu_distribution', 'fee']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->dateTime('transaction_date');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
