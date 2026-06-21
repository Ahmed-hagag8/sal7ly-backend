<?php

namespace App\Http\Controllers\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use App\Models\Favorite;
use Illuminate\Http\Request;

class TechnicianBrowseController extends Controller
{
    /**
     * Browse technicians by category
     *
     * Supports:
     * - Filter by category_id (required)
     * - Filter by city_id (optional)
     * - Search by technician name (optional)
     * - Sort by rating, reviews_count, experience (optional)
     * - Filter by minimum rating (optional)
     * - Filter by availability (optional)
     * - Pagination
     *
     * Returns is_favorite flag for the authenticated customer.
     */
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:service_categories,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'search' => 'nullable|string|max:100',
            'sort' => 'nullable|in:rating,reviews_count,experience,newest',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'available_only' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $customer = $request->user()->customer;
        $customerId = $customer?->id;

        $query = Technician::with(['user', 'category', 'city'])
            ->withCount(['reviews as reviews_count' => function ($q) {
                $q->where('type', 'customer_to_technician');
            }])
            ->where('verification_status', 'approved')
            ->where('service_category_id', $request->category_id);

        // Filter by city
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // Search by technician name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by minimum rating
        if ($request->filled('min_rating')) {
            $query->where('average_rating', '>=', $request->min_rating);
        }

        // Filter by availability
        if ($request->boolean('available_only', false)) {
            $query->where('is_available', true);
        }

        // Only show technicians whose user account is active
        $query->whereHas('user', function ($q) {
            $q->where('is_active', true)->whereNull('deleted_at');
        });

        // Sorting
        $sort = $request->input('sort', 'rating');
        switch ($sort) {
            case 'reviews_count':
                $query->orderByDesc('reviews_count');
                break;
            case 'experience':
                $query->orderByDesc('years_of_experience');
                break;
            case 'newest':
                $query->latest();
                break;
            case 'rating':
            default:
                $query->orderByDesc('average_rating');
                break;
        }

        $perPage = $request->input('per_page', 10);
        $technicians = $query->paginate($perPage);

        // Get customer's favorite technician IDs for marking is_favorite
        $favoriteIds = [];
        if ($customerId) {
            $technicianIds = collect($technicians->items())->pluck('id')->toArray();
            $favoriteIds = Favorite::where('customer_id', $customerId)
                ->whereIn('technician_id', $technicianIds)
                ->pluck('technician_id')
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => collect($technicians->items())->map(fn($tech) => [
                'id' => $tech->id,
                'name' => $tech->user->name,
                'profile_image' => $tech->user->profile_image_url,
                'category' => $tech->category->name ?? null,
                'category_ar' => $tech->category->name_ar ?? null,
                'city' => $tech->city->name ?? null,
                'average_rating' => (float) $tech->average_rating,
                'reviews_count' => (int) $tech->reviews_count,
                'years_of_experience' => $tech->years_of_experience,
                'bio' => $tech->bio,
                'is_available' => $tech->is_available,
                'total_jobs_completed' => $tech->total_jobs_completed,
                'is_favorite' => in_array($tech->id, $favoriteIds),
            ]),
            'meta' => [
                'current_page' => $technicians->currentPage(),
                'last_page' => $technicians->lastPage(),
                'per_page' => $technicians->perPage(),
                'total' => $technicians->total(),
            ],
        ]);
    }

    /**
     * View a single technician's profile
     *
     * Returns detailed info including recent reviews.
     */
    public function show(Request $request, $id)
    {
        $customer = $request->user()->customer;

        $technician = Technician::with(['user', 'category', 'city'])
            ->withCount(['reviews as reviews_count' => function ($q) {
                $q->where('type', 'customer_to_technician');
            }])
            ->where('verification_status', 'approved')
            ->findOrFail($id);

        // Check if favorited
        $isFavorite = false;
        if ($customer) {
            $isFavorite = Favorite::where('customer_id', $customer->id)
                ->where('technician_id', $technician->id)
                ->exists();
        }

        // Get recent reviews (last 10)
        $reviews = $technician->reviews()
            ->where('type', 'customer_to_technician')
            ->with('customer.user')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $technician->id,
                'name' => $technician->user->name,
                'phone' => $technician->user->phone,
                'profile_image' => $technician->user->profile_image_url,
                'category' => $technician->category->name ?? null,
                'category_ar' => $technician->category->name_ar ?? null,
                'city' => $technician->city->name ?? null,
                'bio' => $technician->bio,
                'years_of_experience' => $technician->years_of_experience,
                'average_rating' => (float) $technician->average_rating,
                'reviews_count' => (int) $technician->reviews_count,
                'total_jobs_completed' => $technician->total_jobs_completed,
                'is_available' => $technician->is_available,
                'is_favorite' => $isFavorite,
                'reviews' => $reviews->map(fn($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'customer_name' => $review->customer->user->name ?? 'Anonymous',
                    'created_at' => $review->created_at,
                ]),
            ],
        ]);
    }

    /**
     * Toggle favorite (add/remove)
     *
     * If already favorited → removes it.
     * If not favorited → adds it.
     */
    public function toggleFavorite(Request $request, $technicianId)
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer profile not found',
            ], 404);
        }

        // Verify technician exists and is approved
        $technician = Technician::where('verification_status', 'approved')
            ->find($technicianId);

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'Technician not found',
            ], 404);
        }

        $existing = Favorite::where('customer_id', $customer->id)
            ->where('technician_id', $technicianId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'message' => 'Removed from favorites',
                'is_favorite' => false,
            ]);
        }

        Favorite::create([
            'customer_id' => $customer->id,
            'technician_id' => $technicianId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to favorites',
            'is_favorite' => true,
        ], 201);
    }

    /**
     * List customer's favorite technicians
     */
    public function favorites(Request $request)
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer profile not found',
            ], 404);
        }

        $favorites = Favorite::with(['technician.user', 'technician.category', 'technician.city'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => collect($favorites->items())->map(function ($fav) {
                $tech = $fav->technician;

                // Count reviews
                $reviewsCount = $tech->reviews()
                    ->where('type', 'customer_to_technician')
                    ->count();

                return [
                    'id' => $tech->id,
                    'name' => $tech->user->name,
                    'profile_image' => $tech->user->profile_image_url,
                    'category' => $tech->category->name ?? null,
                    'category_ar' => $tech->category->name_ar ?? null,
                    'city' => $tech->city->name ?? null,
                    'average_rating' => (float) $tech->average_rating,
                    'reviews_count' => $reviewsCount,
                    'is_available' => $tech->is_available,
                    'total_jobs_completed' => $tech->total_jobs_completed,
                    'is_favorite' => true,
                    'favorited_at' => $fav->created_at,
                ];
            }),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }
}
