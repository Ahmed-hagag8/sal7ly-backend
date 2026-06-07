<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Add Arabic name column to service_categories for AI model compatibility.
     * The AI models use Arabic category names (سباكة، كهرباء، نجارة، نقاشة)
     * instead of numeric IDs.
     */
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
        });

        // Populate Arabic names for existing categories
        $mapping = [
            'Plumbing'   => 'سباكة',
            'Electrical' => 'كهرباء',
            'Carpentry'  => 'نجارة',
            'Painting'   => 'نقاشة',
        ];

        foreach ($mapping as $english => $arabic) {
            DB::table('service_categories')
                ->where('name', $english)
                ->update(['name_ar' => $arabic]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn('name_ar');
        });
    }
};
