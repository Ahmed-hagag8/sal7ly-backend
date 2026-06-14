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
        Schema::table('service_images', function (Blueprint $table) {
            $table->decimal('ai_confidence_score', 5, 4)->nullable()->after('ai_result');
            $table->json('ai_detected_objects')->nullable()->after('ai_confidence_score');
            $table->string('ai_suggested_service')->nullable()->after('ai_detected_objects');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_images', function (Blueprint $table) {
            $table->dropColumn([
                'ai_confidence_score',
                'ai_detected_objects',
                'ai_suggested_service',
            ]);
        });
    }
};
