<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * STAB-01: The payments table enum only allowed ['cash', 'wallet'],
     * but PaymentController validation accepted 'card'. This migration
     * adds 'card' to the enum to prevent 500 errors when customers
     * select card payment.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'wallet', 'card') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'wallet') NOT NULL");
    }
};
