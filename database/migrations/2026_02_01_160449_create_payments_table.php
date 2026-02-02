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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string('payment_number')->unique();


            $table->foreignId('job_id')
                ->constrained('jobs');

            $table->foreignId('customer_id')
                ->constrained('customers');

            $table->foreignId('technician_id')
                ->constrained('technicians');

            $table->decimal('amount', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('technician_earnings', 12, 2);

            $table->enum('payment_method', ['cash', 'wallet']);

       
            $table->enum('status', ['pending', 'completed', 'failed'])
                ->default('pending');

         
            $table->timestamp('paid_at')->nullable();

           
            $table->timestamps();

           
            $table->unique('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
