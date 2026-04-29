<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\TechnicianLocation;
use App\Models\Job;
use App\Events\TechnicianLocationUpdated;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Technician sends their current GPS location.
     * POST /api/technician/location
     */
    public function update(Request $request)
    {
        $request->validate([
            'job_id'    => 'required|integer|exists:jobs,id',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'heading'   => 'nullable|numeric|between:0,360',
            'speed'     => 'nullable|numeric|min:0',
        ]);

        $technician = $request->user()->technician;

        // Verify this job belongs to the technician and is in_progress
        $job = Job::where('id', $request->job_id)
            ->where('technician_id', $technician->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        // Upsert the latest location
        TechnicianLocation::updateOrCreate(
            [
                'technician_id' => $technician->id,
                'job_id'        => $job->id,
            ],
            [
                'latitude'   => $request->latitude,
                'longitude'  => $request->longitude,
                'heading'    => $request->heading,
                'speed'      => $request->speed,
                'located_at' => now(),
            ]
        );

        // Broadcast to the customer watching this job
        broadcast(new TechnicianLocationUpdated(
            jobId:     $job->id,
            latitude:  (float) $request->latitude,
            longitude: (float) $request->longitude,
            heading:   $request->heading ? (float) $request->heading : null,
            speed:     $request->speed ? (float) $request->speed : null,
            updatedAt: now()->toISOString(),
        ));

        return response()->json([
            'success' => true,
            'message' => 'Location updated',
        ]);
    }

    /**
     * Customer gets the latest known technician location (REST fallback).
     * GET /api/customer/jobs/{id}/technician-location
     */
    public function show(Request $request, $id)
    {
        $customer = $request->user()->customer;

        $job = Job::where('id', $id)
            ->where('customer_id', $customer->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $location = TechnicianLocation::where('job_id', $job->id)->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'No location available yet',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'latitude'   => $location->latitude,
                'longitude'  => $location->longitude,
                'heading'    => $location->heading,
                'speed'      => $location->speed,
                'located_at' => $location->located_at,
            ],
        ]);
    }
}
