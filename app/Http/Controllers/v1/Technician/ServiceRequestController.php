<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    /**
     * List available requests for technician
     * 
     * Shows only:
     * - Open/pending requests
     * - In technician's city
     * - In technician's service category
     */
    public function index(Request $request)
    {
        $technician = $request->user()->technician;

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'Technician profile not found',
            ], 404);
        }

        $requests = ServiceRequest::with(['category', 'city', 'customer.user'])
            ->withCount('images')
            ->where('city_id', $technician->city_id)
            ->where('category_id', $technician->service_category_id)
            ->whereIn('status', ['pending', 'open'])
            ->whereDoesntHave('offers', function ($q) use ($technician) {
                $q->where('technician_id', $technician->id);
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => collect($requests->items())->map(fn($req) => [
                'id' => $req->id,
                'request_number' => $req->request_number,
                'title' => $req->title,
                'description' => $req->description,
                'category' => $req->category->name ?? null,
                'city' => $req->city->name,
                'address' => $req->address,
                'customer_name' => $req->customer->user->name ?? 'Unknown',
                'preferred_date' => $req->preferred_date,
                'ai_predicted_price' => $req->ai_predicted_price,
                'images_count' => $req->images_count,
                'created_at' => $req->created_at,
            ]),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * View request details
     *
     * SEC-08: Verifies the request is in the technician's city and service
     * category before showing details. Without this, any technician could
     * view any request by ID, leaking customer PII.
     */
    public function show(Request $request, $id)
    {
        $technician = $request->user()->technician;

        $serviceRequest = ServiceRequest::with(['category', 'city', 'customer.user', 'images'])
            ->where('city_id', $technician->city_id)
            ->where('category_id', $technician->service_category_id)
            ->findOrFail($id);

        // Check if technician already made offer
        $existingOffer = $serviceRequest->offers()
            ->where('technician_id', $technician->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $serviceRequest->id,
                'request_number' => $serviceRequest->request_number,
                'title' => $serviceRequest->title,
                'description' => $serviceRequest->description,
                'category' => $serviceRequest->category->name ?? null,
                'city' => $serviceRequest->city->name,
                'address' => $serviceRequest->address,
                'latitude' => $serviceRequest->latitude,
                'longitude' => $serviceRequest->longitude,
                'customer_name' => $serviceRequest->customer->user->name ?? 'Unknown',
                'preferred_date' => $serviceRequest->preferred_date,
                'preferred_time' => $serviceRequest->preferred_time,
                'ai_predicted_price' => $serviceRequest->ai_predicted_price,
                'status' => $serviceRequest->status,
                'images' => $serviceRequest->images->map(fn($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->path),
                ]),
                'my_offer' => $existingOffer ? [
                    'id' => $existingOffer->id,
                    'price' => $existingOffer->offered_price,
                    'status' => $existingOffer->status,
                ] : null,
                'created_at' => $serviceRequest->created_at,
            ],
        ]);
    }
}
