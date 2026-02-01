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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_number')->unique();


            $table->foreignId('wallet_id')
                ->constrained('wallets')
                ->cascadeOnDelete();

            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);

            
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);

            
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            
            $table->string('description')->nullable();

            
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
