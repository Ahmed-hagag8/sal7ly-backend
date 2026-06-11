<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add category_id to service_requests and create new index first
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('customer_id')
                ->constrained('service_categories')->cascadeOnDelete();
                
            $table->index(['city_id', 'category_id', 'status'], 'idx_sr_city_category_status');
        });

        // Migrate data
        $requests = DB::table('service_requests')
            ->join('services', 'service_requests.service_id', '=', 'services.id')
            ->select('service_requests.id', 'services.service_category_id')
            ->get();
            
        foreach ($requests as $request) {
            DB::table('service_requests')->where('id', $request->id)
                ->update(['category_id' => $request->service_category_id]);
        }

        // Make category_id non-nullable, drop service_id and old index
        Schema::table('service_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
            
            $table->dropIndex('idx_sr_city_service_status');
        });

        // Drop services table
        Schema::dropIfExists('services');
    }

    public function down(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('idx_sr_city_category_status');
            
            $table->foreignId('service_id')->nullable()->after('customer_id')
                ->constrained('services')->cascadeOnDelete();
        });

        // Try to map back (impossible accurately without defaults, so just set arbitrary)
        // We will just leave them null, but we need to drop category_id
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            
            $table->index(['city_id', 'service_id', 'status'], 'idx_sr_city_service_status');
        });
    }
};
