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
        Schema::create('service_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_request_id')
                ->constrained('service_requests')
                ->cascadeOnDelete();

            $table->string('path');

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

     
            $table->timestamp('ai_checked_at')->nullable();
            $table->string('ai_result')->nullable();

           
            $table->string('rejection_reason')->nullable();


            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_images');
    }
};
