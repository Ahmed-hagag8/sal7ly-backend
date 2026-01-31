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
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_request_id')
                ->constrained('service_requests')
                ->cascadeOnDelete();

            $table->foreignId('technician_id')
                ->constrained('technicians')
                ->cascadeOnDelete();

            $table->decimal('offered_price', 10, 2);
            $table->unsignedInteger('estimated_duration')->nullable(); // minutes
            $table->text('notes')->nullable();

            $table->enum('status', ['pending', 'accepted', 'rejected'])
                ->default('pending');


            $table->timestamps();


            $table->unique(['service_request_id', 'technician_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
