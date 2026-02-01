<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();

            $table->string('job_number')->unique();


            $table->foreignId('service_request_id')
                ->constrained('service_requests');

            $table->foreignId('job_offer_id')
                ->constrained('job_offers');

            $table->foreignId('customer_id')
                ->constrained('customers');

            $table->foreignId('technician_id')
                ->constrained('technicians');

            $table->decimal('agreed_price', 10, 2);
            $table->decimal('final_price', 10, 2)->nullable();

            $table->enum('status', [
                'scheduled',
                'on_the_way',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('scheduled');


            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

           
            $table->unique('job_offer_id');
            $table->unique('service_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
