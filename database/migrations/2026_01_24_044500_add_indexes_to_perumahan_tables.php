<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add performance indexes to Perumahan module tables
     */
    public function up(): void
    {
        // Indexes for estate_residents (skip if already exists)
        Schema::table('estate_residents', function (Blueprint $table) {
            try {
                if (!$this->indexExists('estate_residents', 'idx_residents_status')) {
                    $table->index('status', 'idx_residents_status');
                }
                if (!$this->indexExists('estate_residents', 'idx_residents_house_status')) {
                    $table->index('house_status', 'idx_residents_house_status');
                }
                if (!$this->indexExists('estate_residents', 'idx_residents_house_type')) {
                    $table->index('house_type', 'idx_residents_house_type');
                }
                if (!$this->indexExists('estate_residents', 'idx_residents_status_house_status')) {
                    $table->index(['status', 'house_status'], 'idx_residents_status_house_status');
                }
            } catch (\Exception $e) {
                // Index may already exist, skip
            }
        });

        // Indexes for estate_services
        Schema::table('estate_services', function (Blueprint $table) {
            try {
                if (!$this->indexExists('estate_services', 'idx_services_house_number')) {
                    $table->index('house_number', 'idx_services_house_number');
                }
                if (!$this->indexExists('estate_services', 'idx_services_status')) {
                    $table->index('status', 'idx_services_status');
                }
                if (!$this->indexExists('estate_services', 'idx_services_category')) {
                    $table->index('category', 'idx_services_category');
                }
                if (!$this->indexExists('estate_services', 'idx_services_priority')) {
                    $table->index('priority', 'idx_services_priority');
                }
                if (!$this->indexExists('estate_services', 'idx_services_status_priority')) {
                    $table->index(['status', 'priority'], 'idx_services_status_priority');
                }
                if (!$this->indexExists('estate_services', 'idx_services_created_at')) {
                    $table->index('created_at', 'idx_services_created_at');
                }
            } catch (\Exception $e) {
                // Skip
            }
        });

        // Indexes for estate_fee_payments
        Schema::table('estate_fee_payments', function (Blueprint $table) {
            try {
                if (!$this->indexExists('estate_fee_payments', 'idx_payments_house_number')) {
                    $table->index('house_number', 'idx_payments_house_number');
                }
                if (!$this->indexExists('estate_fee_payments', 'idx_payments_fee_id')) {
                    $table->index('fee_id', 'idx_payments_fee_id');
                }
                if (!$this->indexExists('estate_fee_payments', 'idx_payments_status')) {
                    $table->index('status', 'idx_payments_status');
                }
                if (!$this->indexExists('estate_fee_payments', 'idx_payments_period')) {
                    $table->index(['period_year', 'period_month'], 'idx_payments_period');
                }
                if (!$this->indexExists('estate_fee_payments', 'idx_payments_house_period')) {
                    $table->index(['house_number', 'period_year', 'period_month'], 'idx_payments_house_period');
                }
                if (!$this->indexExists('estate_fee_payments', 'idx_payments_payment_date')) {
                    $table->index('payment_date', 'idx_payments_payment_date');
                }
                if (!$this->indexExists('estate_fee_payments', 'idx_payments_payment_method')) {
                    $table->index('payment_method', 'idx_payments_payment_method');
                }
            } catch (\Exception $e) {
                // Skip
            }
        });

        // Indexes for estate_fees
        Schema::table('estate_fees', function (Blueprint $table) {
            try {
                if (!$this->indexExists('estate_fees', 'idx_fees_fee_type')) {
                    $table->index('fee_type', 'idx_fees_fee_type');
                }
                if (!$this->indexExists('estate_fees', 'idx_fees_is_active')) {
                    $table->index('is_active', 'idx_fees_is_active');
                }
            } catch (\Exception $e) {
                // Skip
            }
        });

        // Indexes for estate_security_logs
        Schema::table('estate_security_logs', function (Blueprint $table) {
            try {
                if (!$this->indexExists('estate_security_logs', 'idx_security_created_at')) {
                    $table->index('created_at', 'idx_security_created_at');
                }
            } catch (\Exception $e) {
                // Skip
            }
        });

        // Indexes for estate_waste_collections
        Schema::table('estate_waste_collections', function (Blueprint $table) {
            try {
                if (!$this->indexExists('estate_waste_collections', 'idx_waste_house_number')) {
                    $table->index('house_number', 'idx_waste_house_number');
                }
                if (!$this->indexExists('estate_waste_collections', 'idx_waste_collection_date')) {
                    $table->index('collection_date', 'idx_waste_collection_date');
                }
                if (!$this->indexExists('estate_waste_collections', 'idx_waste_is_collected')) {
                    $table->index('is_collected', 'idx_waste_is_collected');
                }
                if (!$this->indexExists('estate_waste_collections', 'idx_waste_date_collected')) {
                    $table->index(['collection_date', 'is_collected'], 'idx_waste_date_collected');
                }
            } catch (\Exception $e) {
                // Skip
            }
        });
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName)
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);
        
        return array_key_exists($indexName, $indexes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes - silently ignore if not exists
        Schema::table('estate_residents', function (Blueprint $table) {
            try { $table->dropIndex('idx_residents_status'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_residents_house_status'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_residents_house_type'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_residents_status_house_status'); } catch (\Exception $e) {}
        });

        Schema::table('estate_services', function (Blueprint $table) {
            try { $table->dropIndex('idx_services_house_number'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_services_status'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_services_category'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_services_priority'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_services_status_priority'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_services_created_at'); } catch (\Exception $e) {}
        });

        Schema::table('estate_fee_payments', function (Blueprint $table) {
            try { $table->dropIndex('idx_payments_house_number'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_payments_fee_id'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_payments_status'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_payments_period'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_payments_house_period'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_payments_payment_date'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_payments_payment_method'); } catch (\Exception $e) {}
        });

        Schema::table('estate_fees', function (Blueprint $table) {
            try { $table->dropIndex('idx_fees_fee_type'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_fees_is_active'); } catch (\Exception $e) {}
        });

        Schema::table('estate_security_logs', function (Blueprint $table) {
            try { $table->dropIndex('idx_security_created_at'); } catch (\Exception $e) {}
        });

        Schema::table('estate_waste_collections', function (Blueprint $table) {
            try { $table->dropIndex('idx_waste_house_number'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_waste_collection_date'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_waste_is_collected'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_waste_date_collected'); } catch (\Exception $e) {}
        });
    }
};
