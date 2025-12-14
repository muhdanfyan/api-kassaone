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
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->string('receipt_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('members')->onDelete('cascade');
            
            // Indexes for performance
            $table->index('account_id');
            $table->index('expense_date');
            $table->index('created_by');
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
