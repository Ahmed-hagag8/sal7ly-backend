<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * List my jobs
     */
    public function index(Request $request)
    {
        $technician = $request->user()->technician;

        $query = Job::with(['serviceRequest.service', 'customer.user'])
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
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
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

        $job = Job::where('technician_id', $request->user()->technician->id)
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

        $job->serviceRequest->update(['status' => 'completed']);

        return response()->json([
            'success' => true,
            'message' => 'Job completed! Customer can pay now.',
        ]);
    }
}
