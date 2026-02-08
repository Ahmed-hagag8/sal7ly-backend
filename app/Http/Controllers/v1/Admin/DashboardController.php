<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Technician, Customer, Job, ServiceRequest, Payment, Withdrawal};
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard statistics
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => User::count(),
                    'customers' => Customer::count(),
                    'technicians' => Technician::count(),
                    'pending_technicians' => Technician::where('verification_status', 'pending')->count(),
                ],
                'jobs' => [
                    'total' => Job::count(),
                    'completed' => Job::where('status', 'completed')->count(),
                    'in_progress' => Job::where('status', 'in_progress')->count(),
                    'scheduled' => Job::where('status', 'scheduled')->count(),
                ],
                'requests' => [
                    'total' => ServiceRequest::count(),
                    'pending' => ServiceRequest::where('status', 'pending')->count(),
                    'open' => ServiceRequest::where('status', 'open')->count(),
                ],
                'financials' => [
                    'total_payments' => Payment::sum('amount'),
                    'total_commission' => Payment::sum('commission_amount'),
                    'pending_withdrawals' => Withdrawal::where('status', 'pending')->sum('amount'),
                ],
            ],
        ]);
    }

    /**
     * Recent activity
     */
    public function recentActivity()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'recent_jobs' => Job::with('customer.user', 'technician.user')
                    ->latest()->take(5)->get(),
                'recent_requests' => ServiceRequest::with('customer.user')
                    ->latest()->take(5)->get(),
                'pending_withdrawals' => Withdrawal::with('user')
                    ->where('status', 'pending')->take(5)->get(),
            ],
        ]);
    }

    /**
     * List all users
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(15),
        ]);
    }
}
