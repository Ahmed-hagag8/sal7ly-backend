<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTechnicianIsVerified
{
    /**
     * Ensure technician is verified before accessing protected features
     * 
     * WHY: Unverified technicians shouldn't accept jobs or make offers
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if not a technician
        if ($user->role !== 'technician') {
            return $next($request);
        }

        // Check technician verification status
        $technician = $user->technician;
        
        if (!$technician || $technician->verification_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending verification. Please wait for admin approval.',
                'verification_status' => $technician?->verification_status ?? 'unknown',
            ], 403);
        }

        return $next($request);
    }
}
