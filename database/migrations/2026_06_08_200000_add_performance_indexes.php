<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PERF-03: Add composite indexes on frequently queried columns.
 *
 * These indexes cover the most common WHERE/ORDER BY patterns
 * found across controllers (status filters, user+status combos,
 * notification reads, review aggregations, OTP lookups).
 */
return new class extends Migration
{
    public function up(): void
    {
        // service_requests: queried by status constantly
        // Individual statements to handle indexes that may already exist from a partial migration run
        try { \DB::statement('ALTER TABLE `service_requests` ADD INDEX `idx_sr_status` (`status`)'); } catch (\Exception $e) {}
        try { \DB::statement('ALTER TABLE `service_requests` ADD INDEX `idx_sr_customer_status` (`customer_id`, `status`)'); } catch (\Exception $e) {}
        try { \DB::statement('ALTER TABLE `service_requests` ADD INDEX `idx_sr_city_service_status` (`city_id`, `category_id`, `status`)'); } catch (\Exception $e) {}

        // jobs: filtered by technician+status and customer+status
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['technician_id', 'status'], 'idx_jobs_tech_status');
            $table->index(['customer_id', 'status'], 'idx_jobs_cust_status');
        });

        // job_offers: filtered by status
        Schema::table('job_offers', function (Blueprint $table) {
            $table->index('status', 'idx_offers_status');
        });

        // withdrawals: filtered by user+status
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_wd_user_status');
        });

        // notifications: filtered by user+is_read, ordered by created_at
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read'], 'idx_notif_user_read');
        });

        // reviews: avg rating queries filter by technician_id+type and customer_id+type
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['technician_id', 'type'], 'idx_rev_tech_type');
            $table->index(['customer_id', 'type'], 'idx_rev_cust_type');
        });

        // password_resets: queried by phone/email + used + expires_at
        Schema::table('password_resets', function (Blueprint $table) {
            $table->index(['phone', 'used', 'expires_at'], 'idx_pr_phone_used_exp');
            $table->index(['email', 'used', 'expires_at'], 'idx_pr_email_used_exp');
        });
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $hasIndex = function ($table, $indexName) use ($connection, $dbName) {
            $result = $connection->select("
                SELECT 1 
                FROM information_schema.statistics 
                WHERE table_schema = ? 
                AND table_name = ? 
                AND index_name = ?
            ", [$dbName, $table, $indexName]);
            return count($result) > 0;
        };

        $ensureIndexForFK = function ($table, $column, $fallbackIndexName, $indexBeingDropped) use ($connection, $dbName) {
            // Check if there is already an index on this column (as the prefix of any index) OTHER than the one being dropped
            $result = $connection->select("
                SELECT 1 
                FROM information_schema.statistics 
                WHERE table_schema = ? 
                AND table_name = ? 
                AND column_name = ?
                AND seq_in_index = 1
                AND index_name != ?
            ", [$dbName, $table, $column, $indexBeingDropped]);

            if (count($result) === 0) {
                // No index exists starting with this column, so create one before we drop the composite one
                Schema::table($table, function (Blueprint $tableObj) use ($column, $fallbackIndexName) {
                    $tableObj->index($column, $fallbackIndexName);
                });
            }
        };

        // service_requests
        if ($hasIndex('service_requests', 'idx_sr_customer_status')) {
            $ensureIndexForFK('service_requests', 'customer_id', 'service_requests_customer_id_idx', 'idx_sr_customer_status');
        }
        if ($hasIndex('service_requests', 'idx_sr_city_service_status')) {
            $ensureIndexForFK('service_requests', 'city_id', 'service_requests_city_id_idx', 'idx_sr_city_service_status');
            $ensureIndexForFK('service_requests', 'category_id', 'service_requests_category_id_idx', 'idx_sr_city_service_status');
        }

        Schema::table('service_requests', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('service_requests', 'idx_sr_status')) {
                $table->dropIndex('idx_sr_status');
            }
            if ($hasIndex('service_requests', 'idx_sr_customer_status')) {
                $table->dropIndex('idx_sr_customer_status');
            }
            if ($hasIndex('service_requests', 'idx_sr_city_service_status')) {
                $table->dropIndex('idx_sr_city_service_status');
            }
        });

        // jobs
        if ($hasIndex('jobs', 'idx_jobs_tech_status')) {
            $ensureIndexForFK('jobs', 'technician_id', 'jobs_technician_id_idx', 'idx_jobs_tech_status');
        }
        if ($hasIndex('jobs', 'idx_jobs_cust_status')) {
            $ensureIndexForFK('jobs', 'customer_id', 'jobs_customer_id_idx', 'idx_jobs_cust_status');
        }

        Schema::table('jobs', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('jobs', 'idx_jobs_tech_status')) {
                $table->dropIndex('idx_jobs_tech_status');
            }
            if ($hasIndex('jobs', 'idx_jobs_cust_status')) {
                $table->dropIndex('idx_jobs_cust_status');
            }
        });

        // job_offers
        Schema::table('job_offers', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('job_offers', 'idx_offers_status')) {
                $table->dropIndex('idx_offers_status');
            }
        });

        // withdrawals
        if ($hasIndex('withdrawals', 'idx_wd_user_status')) {
            $ensureIndexForFK('withdrawals', 'user_id', 'withdrawals_user_id_idx', 'idx_wd_user_status');
        }

        Schema::table('withdrawals', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('withdrawals', 'idx_wd_user_status')) {
                $table->dropIndex('idx_wd_user_status');
            }
        });

        // notifications
        if ($hasIndex('notifications', 'idx_notif_user_read')) {
            $ensureIndexForFK('notifications', 'user_id', 'notifications_user_id_idx', 'idx_notif_user_read');
        }

        Schema::table('notifications', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('notifications', 'idx_notif_user_read')) {
                $table->dropIndex('idx_notif_user_read');
            }
        });

        // reviews
        if ($hasIndex('reviews', 'idx_rev_tech_type')) {
            $ensureIndexForFK('reviews', 'technician_id', 'reviews_technician_id_idx', 'idx_rev_tech_type');
        }
        if ($hasIndex('reviews', 'idx_rev_cust_type')) {
            $ensureIndexForFK('reviews', 'customer_id', 'reviews_customer_id_idx', 'idx_rev_cust_type');
        }

        Schema::table('reviews', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('reviews', 'idx_rev_tech_type')) {
                $table->dropIndex('idx_rev_tech_type');
            }
            if ($hasIndex('reviews', 'idx_rev_cust_type')) {
                $table->dropIndex('idx_rev_cust_type');
            }
        });

        // password_resets
        Schema::table('password_resets', function (Blueprint $table) use ($hasIndex) {
            if ($hasIndex('password_resets', 'idx_pr_phone_used_exp')) {
                $table->dropIndex('idx_pr_phone_used_exp');
            }
            if ($hasIndex('password_resets', 'idx_pr_email_used_exp')) {
                $table->dropIndex('idx_pr_email_used_exp');
            }
        });
    }
};
