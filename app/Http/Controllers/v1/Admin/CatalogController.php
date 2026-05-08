<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\City;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    // ===================== CATEGORIES =====================

    /**
     * Create a new category
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:service_categories,name',
            'name_ar' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = ServiceCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Update a category
     */
    public function updateCategory(Request $request, $id)
    {
        $category = ServiceCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:service_categories,name,' . $id,
            'name_ar' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    /**
     * Delete a category
     */
    public function deleteCategory($id)
    {
        $category = ServiceCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    // ===================== SERVICES =====================

    /**
     * Create a new service
     */
    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $service = Service::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully',
            'data' => $service,
        ], 201);
    }

    /**
     * Update a service
     */
    public function updateService(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'service_category_id' => 'sometimes|exists:service_categories,id',
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully',
            'data' => $service,
        ]);
    }

    /**
     * Delete a service
     */
    public function deleteService($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully',
        ]);
    }

    // ===================== CITIES =====================

    /**
     * List all cities
     */
    public function cities()
    {
        return response()->json([
            'success' => true,
            'data' => City::all(),
        ]);
    }

    /**
     * Create a new city
     */
    public function storeCity(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:cities,name',
            'name_ar' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $city = City::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'City created successfully',
            'data' => $city,
        ], 201);
    }

    /**
     * Update a city
     */
    public function updateCity(Request $request, $id)
    {
        $city = City::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:cities,name,' . $id,
            'name_ar' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $city->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'City updated successfully',
            'data' => $city,
        ]);
    }

    /**
     * Delete a city
     */
    public function deleteCity($id)
    {
        $city = City::findOrFail($id);
        $city->delete();

        return response()->json([
            'success' => true,
            'message' => 'City deleted successfully',
        ]);
    }
}
