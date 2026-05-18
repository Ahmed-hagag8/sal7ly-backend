<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds 'in_progress' status to the service_requests enum.
     * in_progress = technician has started working on the job (between assigned and completed/cancelled).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('pending', 'open', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any in_progress back to assigned
        DB::table('service_requests')->where('status', 'in_progress')->update(['status' => 'assigned']);
        DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('pending', 'open', 'assigned', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};
