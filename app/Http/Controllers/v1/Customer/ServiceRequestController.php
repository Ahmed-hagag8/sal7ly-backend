<?php

namespace App\Http\Controllers\v1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Customer\CreateServiceRequestRequest;
use App\Models\ServiceImage;
use App\Models\ServiceRequest;
use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\NotificationService;

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

        if (!in_array($serviceRequest->status, ['pending', 'open', 'assigned', 'in_progress'])) {
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

    /**
     * List customer's service requests
     */
    public function index(Request $request)
    {
        $customer = $request->user()->customer;

        $query = ServiceRequest::with(['service', 'city'])
            ->where('customer_id', $customer->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => collect($requests->items())->map(fn($req) => [
                'id' => $req->id,
                'request_number' => $req->request_number,
                'title' => $req->title,
                'service' => $req->service->name,
                'city' => $req->city->name,
                'status' => $req->status,
                'offers_count' => $req->offers()->count(),
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
     * List offers on a request
     */
    public function offers(Request $request, $id)
    {
        $customer = $request->user()->customer;
        $serviceRequest = ServiceRequest::where('customer_id', $customer->id)
            ->findOrFail($id);
        $offers = $serviceRequest->offers()
            ->with(['technician.user', 'technician.category'])
            ->where('status', 'pending')
            ->get();
        return response()->json([
            'success' => true,
            'data' => $offers->map(fn($offer) => [
                'id' => $offer->id,
                'technician' => [
                    'id' => $offer->technician->id,
                    'name' => $offer->technician->user->name,
                    'rating' => $offer->technician->average_rating,
                    'jobs_completed' => $offer->technician->total_jobs_completed,
                ],
                'offered_price' => $offer->offered_price,
                'estimated_duration' => $offer->estimated_duration,
                'notes' => $offer->notes,
                'created_at' => $offer->created_at,
            ]),
        ]);
    }
    /**
     * Accept an offer - creates a Job
     */
    public function acceptOffer(Request $request, $requestId, $offerId)
    {
        $customer = $request->user()->customer;
        $serviceRequest = ServiceRequest::where('customer_id', $customer->id)
            ->findOrFail($requestId);
        $offer = JobOffer::where('service_request_id', $requestId)
            ->where('id', $offerId)
            ->where('status', 'pending')
            ->firstOrFail();
        \DB::transaction(function () use ($serviceRequest, $offer, $customer) {
            // Accept this offer
            $offer->update(['status' => 'accepted']);
            // Reject other offers
            JobOffer::where('service_request_id', $serviceRequest->id)
                ->where('id', '!=', $offer->id)
                ->update(['status' => 'rejected']);
            // Update request status
            $serviceRequest->update(['status' => 'assigned']);
            // Create job
            $job = \App\Models\Job::create([
                'job_number' => 'JOB-' . strtoupper(\Str::random(8)),
                'service_request_id' => $serviceRequest->id,
                'job_offer_id' => $offer->id,
                'customer_id' => $customer->id,
                'technician_id' => $offer->technician_id,
                'agreed_price' => $offer->offered_price,
                'status' => 'scheduled',
            ]);
            // Create conversation for chat
            \App\Models\Conversation::create([
                'job_id' => $job->id,
                'participant_1_id' => $customer->user_id,
                'participant_2_id' => $offer->technician->user_id,
                'last_message_at' => now(),
            ]);

            // Notify technician about accepted offer
            NotificationService::send(
                $offer->technician->user_id,
                'offer_accepted',
                'Offer Accepted!',
                'Customer accepted your offer. Start the job!'
            );
        });
        return response()->json([
            'success' => true,
            'message' => 'Offer accepted! Job created.',
        ]);
    }

    /**
     * List customer's jobs
     */
    public function jobs(Request $request)
    {
        $customer = $request->user()->customer;
        $jobs = \App\Models\Job::with(['serviceRequest.service', 'technician.user'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);
        return response()->json([
            'success' => true,
            'data' => collect($jobs->items())->map(fn($job) => [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'service' => $job->serviceRequest->service->name,
                'technician_name' => $job->technician->user->name,
                'agreed_price' => $job->agreed_price,
                'final_price' => $job->final_price,
                'status' => $job->status,
                'is_paid' => $job->payment()->exists(),
            ]),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    /**
     * Show single job detail for customer
     */
    public function showJob(Request $request, $id)
    {
        $customer = $request->user()->customer;
        $job = \App\Models\Job::with(['serviceRequest.service', 'serviceRequest.city', 'technician.user', 'payment'])
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'service' => $job->serviceRequest->service->name,
                'title' => $job->serviceRequest->title,
                'description' => $job->serviceRequest->description,
                'address' => $job->serviceRequest->address,
                'city' => $job->serviceRequest->city->name ?? null,
                'technician' => [
                    'id' => $job->technician->id,
                    'name' => $job->technician->user->name,
                    'phone' => $job->technician->user->phone,
                    'rating' => $job->technician->average_rating,
                    'profile_image' => $job->technician->user->profile_image
                        ? asset('storage/' . $job->technician->user->profile_image)
                        : null,
                ],
                'agreed_price' => $job->agreed_price,
                'final_price' => $job->final_price,
                'status' => $job->status,
                'started_at' => $job->started_at,
                'completed_at' => $job->completed_at,
                'is_paid' => $job->payment !== null,
                'payment' => $job->payment ? [
                    'payment_number' => $job->payment->payment_number,
                    'amount' => $job->payment->amount,
                    'method' => $job->payment->payment_method,
                    'paid_at' => $job->payment->paid_at,
                ] : null,
                'has_reviewed' => \App\Models\Review::where('job_id', $job->id)
                    ->where('type', 'customer_to_technician')->exists(),
                'created_at' => $job->created_at,
            ],
        ]);
    }


}
