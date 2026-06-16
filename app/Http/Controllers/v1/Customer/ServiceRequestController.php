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
use App\Helpers\UniqueNumberGenerator;

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

        $category = \App\Models\ServiceCategory::find($request->category_id);
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        // ── AI Image Validation (before creating the request) ──────────
        $aiResults = []; // Store AI response per image index
        $invalidImages = []; // Track which images failed
        $aiServiceAvailable = true;

        if ($request->hasFile('images')) {
            $aiBaseUrl = config('services.ai.url');

            foreach ($request->file('images') as $index => $image) {
                try {
                    $httpRequest = \Illuminate\Support\Facades\Http::timeout(30)
                        ->attach('image', file_get_contents($image), 'image.jpg');

                    // Send description to enable full AI matching pipeline
                    if ($request->filled('description')) {
                        $httpRequest = \Illuminate\Support\Facades\Http::timeout(30)
                            ->attach('image', file_get_contents($image), 'image.jpg')
                            ->attach('description', $request->description);
                    }

                    $response = $httpRequest->post("{$aiBaseUrl}/detect-image");

                    if ($response->successful()) {
                        $data = $response->json();
                        $aiResults[$index] = $data;

                        if (!($data['is_valid'] ?? false)) {
                            $invalidImages[] = [
                                'image_index' => $index + 1,
                                'message' => $data['message'] ?? 'Image is not valid for this service.',
                                'detected_objects' => $data['detected_objects'] ?? [],
                            ];
                        }
                    } else {
                        // AI returned an error response — treat as unavailable for this image
                        $aiResults[$index] = null;
                        $aiServiceAvailable = false;
                    }
                } catch (\Exception $e) {
                    // AI service is down — graceful degradation
                    $aiResults[$index] = null;
                    $aiServiceAvailable = false;
                }
            }

            // If AI was available and some images are invalid, reject the entire request
            if ($aiServiceAvailable && count($invalidImages) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some images are not valid for this service. Please upload valid images.',
                    'invalid_images' => $invalidImages,
                ], 422);
            }
        }
        // ── End AI Image Validation ────────────────────────────────────

        $serviceRequest = DB::transaction(function () use ($request, $customer, $category, $aiResults, $aiServiceAvailable) {
            // Generate unique request number with collision protection
            $requestNumber = UniqueNumberGenerator::generate('REQ-', 'service_requests', 'request_number');

            // Create service request
            $serviceRequest = ServiceRequest::create([
                'request_number' => $requestNumber,
                'customer_id' => $customer->id,
                'category_id' => $category->id,
                'city_id' => $request->city_id,
                'title' => 'Request for ' . $category->name,
                'description' => $request->description,
                'address' => $request->address,
                'latitude' => $request->latitude ?? $customer->latitude,
                'longitude' => $request->longitude ?? $customer->longitude,
                'preferred_date' => $request->preferred_date,
                'preferred_time' => $request->preferred_time,
                'status' => 'pending',
                'customer_proposed_price' => $request->customer_proposed_price,
                'ai_predicted_price' => $request->ai_predicted_price,
            ]);

            // Upload images if provided (with AI audit data)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store(
                        "service_requests/{$serviceRequest->id}",
                        'public'
                    );

                    $aiData = $aiResults[$index] ?? null;

                    ServiceImage::create([
                        'service_request_id' => $serviceRequest->id,
                        'path' => $path,
                        'status' => $aiData ? ($aiData['is_valid'] ? 'approved' : 'rejected') : 'pending',
                        'ai_checked_at' => $aiData ? now() : null,
                        'ai_result' => $aiData ? ($aiData['is_valid'] ? 'valid' : 'invalid') : null,
                        'ai_confidence_score' => $aiData['confidence_score'] ?? null,
                        'ai_detected_objects' => $aiData['detected_objects'] ?? null,
                        'ai_suggested_service' => $aiData['suggested_service'] ?? null,
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

        $serviceRequest = ServiceRequest::with(['category', 'city', 'images'])
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

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
                'preferred_date' => $serviceRequest->preferred_date,
                'preferred_time' => $serviceRequest->preferred_time,
                'status' => $serviceRequest->status,
                'ai_predicted_price' => $serviceRequest->ai_predicted_price,
                'customer_proposed_price' => $serviceRequest->customer_proposed_price,
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

    /**
     * List customer's service requests
     */
    public function index(Request $request)
    {
        $customer = $request->user()->customer;

        $query = ServiceRequest::with(['category', 'city'])
            ->withCount('offers')
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
                'category' => $req->category->name ?? null,
                'city' => $req->city->name,
                'status' => $req->status,
                'customer_proposed_price' => $req->customer_proposed_price,
                'offers_count' => $req->offers_count,
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
                'job_number' => UniqueNumberGenerator::generate('JOB-', 'jobs', 'job_number'),
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
        $jobs = \App\Models\Job::with(['serviceRequest.category', 'technician.user', 'payment'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);
        return response()->json([
            'success' => true,
            'data' => collect($jobs->items())->map(fn($job) => [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'category' => $job->serviceRequest->category->name ?? null,
                'technician_name' => $job->technician->user->name,
                'agreed_price' => $job->agreed_price,
                'final_price' => $job->final_price,
                'status' => $job->status,
                'is_paid' => $job->payment !== null,
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
        $job = \App\Models\Job::with(['serviceRequest.category', 'serviceRequest.city', 'technician.user', 'payment'])
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'category' => $job->serviceRequest->category->name ?? null,
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
