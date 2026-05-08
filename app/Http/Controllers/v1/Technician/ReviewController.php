<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Technician reviews a customer after a completed & paid job.
     * Mirrors Customer\ReviewController::store but for the other direction.
     */
    public function store(Request $request, $jobId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $technician = $request->user()->technician;
        $job = Job::where('technician_id', $technician->id)
            ->where('id', $jobId)
            ->whereHas('payment') // Must be paid
            ->firstOrFail();

        // Check if technician already reviewed this customer for this job
        $existing = Review::where('job_id', $job->id)
            ->where('type', 'technician_to_customer')
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Already reviewed this customer'], 400);
        }

        $review = Review::create([
            'job_id' => $job->id,
            'customer_id' => $job->customer_id,
            'technician_id' => $technician->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'type' => 'technician_to_customer',
        ]);

        // Update customer average rating (only from technician_to_customer reviews)
        $customer = $job->customer;
        $avg = Review::where('customer_id', $customer->id)
            ->where('type', 'technician_to_customer')
            ->avg('rating');
        $customer->update(['average_rating' => round($avg, 2)]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your review!',
        ], 201);
    }
}
