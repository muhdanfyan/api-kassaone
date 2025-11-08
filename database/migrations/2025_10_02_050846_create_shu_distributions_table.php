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
        Schema::create('shu_distributions', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->integer('fiscal_year')->unique();
            $table->decimal('total_shu_amount', 15, 2);
            $table->decimal('reserve_percentage', 5, 2)->default(0);
            $table->decimal('member_service_percentage', 5, 2)->default(0);
            $table->decimal('management_service_percentage', 5, 2)->default(0);
            $table->decimal('education_percentage', 5, 2)->default(0);
            $table->decimal('social_percentage', 5, 2)->default(0);
            $table->decimal('zakat_percentage', 5, 2)->default(0);
            $table->date('distribution_date');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shu_distributions');
    }
};
