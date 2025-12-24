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
        Schema::create('expenses', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('account_id', 26);
            $table->text('description');
            $table->decimal('unit_price', 15, 2);
            $table->string('unit', 20)->default('pcs');
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->string('receipt_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 26)->nullable();
            $table->timestamps();
            
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('members')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
