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
            // Informasi Pribadi
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->nullable()->after('address');
            $table->string('birth_place')->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->string('religion')->nullable()->after('birth_date');
            $table->string('education')->nullable()->after('religion');
            $table->string('occupation')->nullable()->after('education');
            $table->string('office_name')->nullable()->after('occupation');
            $table->enum('marital_status', ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'])->nullable()->after('office_name');
            
            // Informasi Ahli Waris
            $table->string('heir_name')->nullable()->after('marital_status');
            $table->string('heir_relationship')->nullable()->after('heir_name');
            $table->text('heir_address')->nullable()->after('heir_relationship');
            $table->string('heir_phone')->nullable()->after('heir_address');
            
            // Simpanan Bulanan
            $table->decimal('monthly_savings_amount', 15, 2)->default(500000)->after('payment_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'birth_place',
                'birth_date',
                'religion',
                'education',
                'occupation',
                'office_name',
                'marital_status',
                'heir_name',
                'heir_relationship',
                'heir_address',
                'heir_phone',
                'monthly_savings_amount',
            ]);
        });
    }
};
