<?php

namespace App\Http\Controllers\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, $jobId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $customer = $request->user()->customer;
        $job = Job::where('customer_id', $customer->id)
            ->where('id', $jobId)
            ->whereHas('payment') // Must be paid
            ->firstOrFail();

        if ($job->review) {
            return response()->json(['success' => false, 'message' => 'Already reviewed'], 400);
        }

        $review = Review::create([
            'job_id' => $job->id,
            'customer_id' => $customer->id,
            'technician_id' => $job->technician_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Update technician average
        $technician = $job->technician;
        $avg = Review::where('technician_id', $technician->id)->avg('rating');
        $technician->update(['average_rating' => round($avg, 2)]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your review!',
        ], 201);
    }
}
