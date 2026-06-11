<?php

namespace App\Http\Controllers\v1\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Cache;

class ServiceCategoryController extends Controller
{
    /**
     * List all active service categories
     * 
     * WHY CACHE: This is called on app home screen for every user.
     * Cache for 1 hour to reduce DB load.
     */
    public function index()
    {
        $categories = Cache::remember('service_categories', 3600, function () {
            return ServiceCategory::where('is_active', true)
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => ServiceCategoryResource::collection($categories),
        ]);
    }

    /**
     * Get single category with its services
     */
    public function show($id)
    {
        $category = Cache::remember("service_category_{$id}", 3600, function () use ($id) {
            return ServiceCategory::find($id); // PERF-06: Use find() instead of findOrFail() to avoid caching exceptions
        });

        if (!$category) {
            Cache::forget("service_category_{$id}"); // Clear stale cache
            abort(404, 'Category not found');
        }

        return response()->json([
            'success' => true,
            'data' => new ServiceCategoryResource($category),
        ]);
    }
}
