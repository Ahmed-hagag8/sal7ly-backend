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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->index('status', 'idx_sr_status');
            $table->index(['customer_id', 'status'], 'idx_sr_customer_status');
            $table->index(['city_id', 'service_id', 'status'], 'idx_sr_city_service_status');
        });

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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('idx_sr_status');
            $table->dropIndex('idx_sr_customer_status');
            $table->dropIndex('idx_sr_city_service_status');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_tech_status');
            $table->dropIndex('idx_jobs_cust_status');
        });

        Schema::table('job_offers', function (Blueprint $table) {
            $table->dropIndex('idx_offers_status');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropIndex('idx_wd_user_status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notif_user_read');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_rev_tech_type');
            $table->dropIndex('idx_rev_cust_type');
        });

        Schema::table('password_resets', function (Blueprint $table) {
            $table->dropIndex('idx_pr_phone_used_exp');
            $table->dropIndex('idx_pr_email_used_exp');
        });
    }
};
