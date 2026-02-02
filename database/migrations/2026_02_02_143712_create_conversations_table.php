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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();


            $table->foreignId('service_request_id')
                ->nullable()
                ->constrained('service_requests')
                ->cascadeOnDelete();

            $table->foreignId('job_id')
                ->nullable()
                ->constrained('jobs')
                ->cascadeOnDelete();


            $table->foreignId('participant_1_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('participant_2_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('last_message_at')->nullable();

            
            $table->timestamps();

            
            $table->unique('job_id');
            $table->unique('service_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
