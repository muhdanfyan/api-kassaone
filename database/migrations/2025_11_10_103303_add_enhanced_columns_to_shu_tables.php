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
        // Add columns to shu_distributions table
        Schema::table('shu_distributions', function (Blueprint $table) {
            $table->decimal('cadangan_amount', 15, 2)->nullable()->after('total_shu_amount');
            $table->decimal('jasa_modal_amount', 15, 2)->nullable()->after('cadangan_amount');
            $table->decimal('jasa_usaha_amount', 15, 2)->nullable()->after('jasa_modal_amount');
            $table->enum('status', ['draft', 'approved', 'paid_out'])->default('draft')->after('jasa_usaha_amount');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->string('approved_by', 25)->nullable()->after('approved_at');
            
            $table->foreign('approved_by')->references('id')->on('members')->onDelete('set null');
        });

        // Add columns to shu_member_allocations table
        Schema::table('shu_member_allocations', function (Blueprint $table) {
            $table->decimal('jasa_modal_amount', 15, 2)->nullable()->after('member_id');
            $table->decimal('jasa_usaha_amount', 15, 2)->nullable()->after('jasa_modal_amount');
            $table->timestamp('paid_out_at')->nullable()->after('is_paid_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shu_distributions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'cadangan_amount',
                'jasa_modal_amount',
                'jasa_usaha_amount',
                'status',
                'approved_at',
                'approved_by'
            ]);
        });

        Schema::table('shu_member_allocations', function (Blueprint $table) {
            $table->dropColumn([
                'jasa_modal_amount',
                'jasa_usaha_amount',
                'paid_out_at'
            ]);
        });
    }
};
