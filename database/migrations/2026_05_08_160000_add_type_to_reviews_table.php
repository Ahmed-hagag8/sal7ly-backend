<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Adds a 'type' column to distinguish between:
     * - 'customer_to_technician' (customer rates technician after a job)
     * - 'technician_to_customer' (technician rates customer after a job)
     *
     * Changes unique constraint from job_id alone to (job_id, type)
     * so both parties can leave one review per job.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Add type column if it doesn't exist yet
            if (!Schema::hasColumn('reviews', 'type')) {
                $table->enum('type', ['customer_to_technician', 'technician_to_customer'])
                    ->default('customer_to_technician')
                    ->after('comment');
            }

            // Re-add the foreign key and new composite unique
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->unique(['job_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->dropUnique(['job_id', 'type']);
            $table->dropColumn('type');

            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->unique('job_id');
        });
    }
};
