<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Adds Stripe-specific columns and expands the status enum to support
     * the async Stripe PaymentIntent flow (requires_payment → completed/failed).
     */
    public function up(): void
    {
        // Add Stripe columns
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->index()->after('paid_at');
            $table->string('stripe_charge_id')->nullable()->after('stripe_payment_intent_id');
            $table->string('stripe_client_secret')->nullable()->after('stripe_charge_id');
        });

        // Expand status enum to include 'requires_payment' and 'refunded'
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'requires_payment', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending'");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_payment_intent_id',
                'stripe_charge_id',
                'stripe_client_secret',
            ]);
        });
    }
};
