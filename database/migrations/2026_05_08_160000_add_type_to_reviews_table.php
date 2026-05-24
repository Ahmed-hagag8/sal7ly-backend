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
        // Add type column if it doesn't exist yet
        if (!Schema::hasColumn('reviews', 'type')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->enum('type', ['customer_to_technician', 'technician_to_customer'])
                    ->default('customer_to_technician')
                    ->after('comment');
            });
        }

        Schema::table('reviews', function (Blueprint $table) {
            $indexes = Schema::getIndexes('reviews');
            
            $hasUniqueJobId = collect($indexes)->contains(function ($index) {
                return $index['name'] === 'reviews_job_id_unique';
            });

            $hasCompositeUnique = collect($indexes)->contains(function ($index) {
                return $index['name'] === 'reviews_job_id_type_unique';
            });

            // Create new composite unique constraint first so foreign key remains supported in MySQL
            if (!$hasCompositeUnique) {
                $table->unique(['job_id', 'type']);
            }

            // Drop the old single unique constraint
            if ($hasUniqueJobId) {
                $table->dropUnique('reviews_job_id_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $indexes = Schema::getIndexes('reviews');
            
            $hasCompositeUnique = collect($indexes)->contains(function ($index) {
                return $index['name'] === 'reviews_job_id_type_unique';
            });

            $hasUniqueJobId = collect($indexes)->contains(function ($index) {
                return $index['name'] === 'reviews_job_id_unique';
            });

            if (!$hasUniqueJobId) {
                $table->unique('job_id');
            }

            if ($hasCompositeUnique) {
                $table->dropUnique(['job_id', 'type']);
            }

            if (Schema::hasColumn('reviews', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
