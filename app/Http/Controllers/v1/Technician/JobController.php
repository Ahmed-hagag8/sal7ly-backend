<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use App\Models\TechnicianLocation;

class JobController extends Controller
{
    /**
     * List my jobs
     */
    public function index(Request $request)
    {
        $technician = $request->user()->technician;

        $query = Job::with(['serviceRequest.category', 'customer.user'])
            ->where('technician_id', $technician->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        /** @var \Illuminate\Pagination\LengthAwarePaginator $jobs */

        $jobs = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $jobs->getCollection()->map(fn($job) => [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'title' => $job->serviceRequest->title,
                'customer_name' => $job->customer->user->name,
                'address' => $job->serviceRequest->address,
                'agreed_price' => $job->agreed_price,
                'status' => $job->status,
                'created_at' => $job->created_at,
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
     * Show single job detail
     */
    public function show(Request $request, $id)
    {
        $technician = $request->user()->technician;
        $job = Job::with(['serviceRequest.category', 'serviceRequest.city', 'serviceRequest.images', 'customer.user', 'payment'])
            ->where('technician_id', $technician->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'service' => $job->serviceRequest->category->name ?? null,
                'title' => $job->serviceRequest->title,
                'description' => $job->serviceRequest->description,
                'address' => $job->serviceRequest->address,
                'city' => $job->serviceRequest->city->name ?? null,
                'latitude' => $job->serviceRequest->latitude,
                'longitude' => $job->serviceRequest->longitude,
                'customer' => [
                    'id' => $job->customer->id,
                    'name' => $job->customer->user->name,
                    'phone' => $job->customer->user->phone,
                    'rating' => $job->customer->average_rating,
                ],
                'agreed_price' => $job->agreed_price,
                'final_price' => $job->final_price,
                'status' => $job->status,
                'started_at' => $job->started_at,
                'completed_at' => $job->completed_at,
                'is_paid' => $job->payment !== null,
                'has_reviewed' => \App\Models\Review::where('job_id', $job->id)
                    ->where('type', 'technician_to_customer')->exists(),
                'images' => $job->serviceRequest->images->map(fn($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->path),
                ]),
                'created_at' => $job->created_at,
            ],
        ]);
    }

    /**
     * Start job
     */
    public function start(Request $request, $id)
    {
        $job = Job::where('technician_id', $request->user()->technician->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($job->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Job cannot be started',
            ], 400);
        }

        $job->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Update service request status to in_progress
        $job->serviceRequest->update(['status' => 'in_progress']);

        return response()->json([
            'success' => true,
            'message' => 'Job started!',
        ]);
    }

    /**
     * Complete job
     */
    public function complete(Request $request, $id)
    {
        $request->validate([
            'final_price' => 'nullable|numeric|min:0',
        ]);

        $job = Job::with(['serviceRequest', 'technician']) // PERF-12: Eager-load relations used below
            ->where('technician_id', $request->user()->technician->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($job->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Job not in progress',
            ], 400);
        }

        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
            'final_price' => $request->final_price ?? $job->agreed_price,
        ]);
        TechnicianLocation::where('job_id', $job->id)->delete();

        $job->serviceRequest->update(['status' => 'completed']);

        // Increment technician's completed jobs counter
        $job->technician->increment('total_jobs_completed');

        return response()->json([
            'success' => true,
            'message' => 'Job completed! Customer can pay now.',
        ]);
    }
}
