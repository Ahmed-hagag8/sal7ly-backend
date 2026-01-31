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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_number')->unique();


            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->foreignId('service_id')
                ->constrained('services');

            $table->string('title');
            $table->text('description');


            $table->string('address');
            $table->foreignId('city_id')
                ->constrained('cities');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->date('preferred_date')->nullable();
            $table->time('preferred_time')->nullable();

        
            $table->enum('status', [
                'pending',
                'open',
                'assigned',
                'completed',
                'cancelled'
            ])->default('pending');

            
            $table->decimal('ai_predicted_price', 10, 2)->nullable();

            
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
