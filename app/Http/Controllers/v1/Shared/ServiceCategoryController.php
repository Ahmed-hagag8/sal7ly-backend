<?php

namespace App\Http\Controllers\v1\Shared;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCategoryResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
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
                ->withCount('services')
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
            return ServiceCategory::with(['services' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])->find($id); // PERF-06: Use find() instead of findOrFail() to avoid caching exceptions
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

    /**
     * List services by category
     */
    public function services($categoryId)
    {
        $services = Cache::remember("category_{$categoryId}_services", 3600, function () use ($categoryId) {
            return Service::where('service_category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => ServiceResource::collection($services),
        ]);
    }

    /**
     * List all services (for search/filter)
     */
    public function allServices()
    {
        $services = Cache::remember('all_services', 3600, function () {
            return Service::with('category')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => ServiceResource::collection($services),
        ]);
    }
}
