<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * STAB-02: The withdrawals table enum only allowed ['bank_transfer', 'vodafone_cash'],
     * but WithdrawalController validation accepted 'instapay'. This migration
     * adds 'instapay' to the enum to prevent 500 errors when technicians
     * select instapay as withdrawal method.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE withdrawals MODIFY COLUMN method ENUM('bank_transfer', 'vodafone_cash', 'instapay') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE withdrawals MODIFY COLUMN method ENUM('bank_transfer', 'vodafone_cash') NOT NULL");
    }
};
