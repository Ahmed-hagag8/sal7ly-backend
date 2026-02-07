<?php

namespace App\Http\Controllers\v1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Customer\CreateServiceRequestRequest;
use App\Models\ServiceImage;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceRequestController extends Controller
{
    /**
     * Create new service request
     */
    public function store(CreateServiceRequestRequest $request)
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer profile not found',
            ], 404);
        }

        $serviceRequest = DB::transaction(function () use ($request, $customer) {
            // Generate unique request number
            $requestNumber = 'REQ-' . strtoupper(Str::random(8));

            // Create service request
            $serviceRequest = ServiceRequest::create([
                'request_number' => $requestNumber,
                'customer_id' => $customer->id,
                'service_id' => $request->service_id,
                'city_id' => $request->city_id,
                'title' => $request->title,
                'description' => $request->description,
                'address' => $request->address,
                'latitude' => $request->latitude ?? $customer->latitude,
                'longitude' => $request->longitude ?? $customer->longitude,
                'preferred_date' => $request->preferred_date,
                'preferred_time' => $request->preferred_time,
                'status' => 'pending',
            ]);

            // Upload images if provided
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store(
                        "service_requests/{$serviceRequest->id}",
                        'public'
                    );

                    ServiceImage::create([
                        'service_request_id' => $serviceRequest->id,
                        'path' => $path,
                        'status' => 'pending', // AI will check later
                    ]);
                }
            }

            return $serviceRequest;
        });

        return response()->json([
            'success' => true,
            'message' => 'Service request created successfully',
            'data' => [
                'id' => $serviceRequest->id,
                'request_number' => $serviceRequest->request_number,
                'status' => $serviceRequest->status,
            ],
        ], 201);
    }

    /**
     * Show single request details
     */
    public function show(Request $request, $id)
    {
        $customer = $request->user()->customer;

        $serviceRequest = ServiceRequest::with(['service', 'city', 'images'])
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $serviceRequest->id,
                'request_number' => $serviceRequest->request_number,
                'title' => $serviceRequest->title,
                'description' => $serviceRequest->description,
                'service' => $serviceRequest->service->name,
                'city' => $serviceRequest->city->name,
                'address' => $serviceRequest->address,
                'preferred_date' => $serviceRequest->preferred_date,
                'preferred_time' => $serviceRequest->preferred_time,
                'status' => $serviceRequest->status,
                'ai_predicted_price' => $serviceRequest->ai_predicted_price,
                'images' => $serviceRequest->images->map(fn($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->path),
                    'status' => $img->status,
                ]),
                'created_at' => $serviceRequest->created_at,
            ],
        ]);
    }

    /**
     * Cancel a pending request
     */
    public function cancel(Request $request, $id)
    {
        $customer = $request->user()->customer;

        $serviceRequest = ServiceRequest::where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$serviceRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], 404);
        }

        if (!in_array($serviceRequest->status, ['pending', 'open'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel request in current status',
            ], 400);
        }

        $serviceRequest->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Request cancelled successfully',
        ]);
    }
}
