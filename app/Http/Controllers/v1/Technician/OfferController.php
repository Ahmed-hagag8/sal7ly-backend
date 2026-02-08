<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use App\Models\ServiceRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    /**
     * Make offer on a service request
     */
    public function store(Request $request, $requestId)
    {
        $request->validate([
            'offered_price' => 'required|numeric|min:1',
            'estimated_duration' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $technician = $request->user()->technician;
        $serviceRequest = ServiceRequest::findOrFail($requestId);

        // Check if request is still open
        if (!in_array($serviceRequest->status, ['pending', 'open'])) {
            return response()->json([
                'success' => false,
                'message' => 'This request is no longer accepting offers',
            ], 400);
        }

        // Check if already made offer
        $existing = JobOffer::where('service_request_id', $requestId)
            ->where('technician_id', $technician->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already made an offer on this request',
            ], 400);
        }

        $offer = JobOffer::create([
            'service_request_id' => $requestId,
            'technician_id' => $technician->id,
            'offered_price' => $request->offered_price,
            'estimated_duration' => $request->estimated_duration,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        // Update request status to open if first offer
        if ($serviceRequest->status === 'pending') {
            $serviceRequest->update(['status' => 'open']);
        }

        // Notify customer about new offer
        NotificationService::send(
            $serviceRequest->customer->user_id,
            'new_offer',
            'New Offer!',
            "Technician offered {$offer->offered_price} EGP for your request"
        );

        return response()->json([
            'success' => true,
            'message' => 'Offer submitted successfully',
            'data' => [
                'id' => $offer->id,
                'offered_price' => $offer->offered_price,
                'status' => $offer->status,
            ],
        ], 201);
    }

    /**
     * List my offers
     */
    public function index(Request $request)
    {
        $technician = $request->user()->technician;

        /** @var \Illuminate\Pagination\LengthAwarePaginator $offers */
        $offers = JobOffer::with(['serviceRequest.service', 'serviceRequest.customer.user'])
            ->where('technician_id', $technician->id)
            ->latest()
            ->paginate(10);

        $offers->setCollection($offers->getCollection()->map(fn($offer) => [
            'id' => $offer->id,
            'request_number' => $offer->serviceRequest->request_number,
            'title' => $offer->serviceRequest->title,
            'offered_price' => $offer->offered_price,
            'status' => $offer->status,
            'created_at' => $offer->created_at,
        ]));

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    }

    /**
     * Withdraw pending offer
     */
    public function destroy(Request $request, $id)
    {
        $technician = $request->user()->technician;

        $offer = JobOffer::where('technician_id', $technician->id)
            ->where('id', $id)
            ->first();

        if (!$offer || $offer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot withdraw this offer',
            ], 400);
        }

        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Offer withdrawn',
        ]);
    }
}
