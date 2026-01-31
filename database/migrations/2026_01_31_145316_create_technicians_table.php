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
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();


            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();


            $table->foreignId('service_category_id')
                ->constrained('service_categories');

            $table->unsignedInteger('years_of_experience')->nullable();
            $table->text('bio')->nullable();

            $table->foreignId('city_id')
                ->constrained('cities');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();


            $table->enum('verification_status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');

            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_jobs_completed')->default(0);

            $table->boolean('is_available')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->unique('user_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};
