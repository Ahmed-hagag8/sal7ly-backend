<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle role-based access
     * 
     * Usage in routes:
     * Route::middleware('role:admin')        // Admin only
     * Route::middleware('role:customer')     // Customer only
     * Route::middleware('role:technician')   // Technician only
     * Route::middleware('role:admin,technician') // Admin OR Technician
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if user has required role
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this resource',
                'required_role' => count($roles) === 1 ? $roles[0] : $roles,
            ], 403);
        }

        return $next($request);
    }
}
