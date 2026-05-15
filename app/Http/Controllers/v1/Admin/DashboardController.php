<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Technician, Customer, Job, ServiceRequest, Payment, Withdrawal, Review};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard statistics
     */
    public function stats()
    {
        // Overview cards
        $totalRequestsThisMonth = ServiceRequest::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
        $totalRequestsLastMonth = ServiceRequest::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)->count();
        $requestsGrowth = $totalRequestsLastMonth > 0
            ? round((($totalRequestsThisMonth - $totalRequestsLastMonth) / $totalRequestsLastMonth) * 100, 1)
            : 0;

        $newCustomers = Customer::whereMonth('created_at', now()->month)->count();
        $newCustomersLastMonth = Customer::whereMonth('created_at', now()->subMonth()->month)->count();
        $customersGrowth = $newCustomersLastMonth > 0
            ? round((($newCustomers - $newCustomersLastMonth) / $newCustomersLastMonth) * 100, 1)
            : 0;

        $activeTechniciansToday = Job::whereDate('updated_at', today())
            ->whereIn('status', ['in_progress', 'completed'])
            ->distinct('technician_id')->count('technician_id');

        $cancelledThisMonth = ServiceRequest::whereMonth('created_at', now()->month)
            ->where('status', 'cancelled')->count();
        $cancellationRate = $totalRequestsThisMonth > 0
            ? round(($cancelledThisMonth / $totalRequestsThisMonth) * 100, 1)
            : 0;
        $cancelledLastMonth = ServiceRequest::whereMonth('created_at', now()->subMonth()->month)
            ->where('status', 'cancelled')->count();
        $cancellationRateLastMonth = $totalRequestsLastMonth > 0
            ? round(($cancelledLastMonth / $totalRequestsLastMonth) * 100, 1)
            : 0;
        $cancellationChange = round($cancellationRate - $cancellationRateLastMonth, 1);

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_requests_this_month' => $totalRequestsThisMonth,
                    'requests_growth' => $requestsGrowth,
                    'new_customers' => $newCustomers,
                    'customers_growth' => $customersGrowth,
                    'active_technicians_today' => $activeTechniciansToday,
                    'cancellation_rate' => $cancellationRate,
                    'cancellation_change' => $cancellationChange,
                ],
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
        $query = User::with(['customer.city', 'technician']);

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

        /** @var \Illuminate\Pagination\LengthAwarePaginator $users */
        $users = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users->through(function ($user) {
                $item = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                ];

                // Add rating and city for customers
                if ($user->role === 'customer' && $user->customer) {
                    $item['average_rating'] = $user->customer->average_rating;
                    $item['city'] = $user->customer->city->name ?? null;
                }

                // Add rating for technicians
                if ($user->role === 'technician' && $user->technician) {
                    $item['average_rating'] = $user->technician->average_rating;
                }

                return $item;
            }),
        ]);
    }
    /**
     * List all transactions (payments)
     */
    public function transactions(Request $request)
    {
        $query = Payment::with(['customer.user', 'technician.user', 'job.serviceRequest.service']);
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        /** @var \Illuminate\Pagination\LengthAwarePaginator $transactions */
        $transactions = $query->latest()->paginate(20);
        $transactions = $transactions->through(fn($p) => [
            'transaction_id' => $p->payment_number,
            'date' => $p->created_at->format('Y-m-d'),
            'customer' => $p->customer->user->name,
            'technician' => $p->technician->user->name,
            'service' => $p->job->serviceRequest->service->name ?? 'N/A',
            'amount' => $p->amount,
            'status' => $p->status,
        ]);
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
    /**
     * List all withdrawals
     */
    public function withdrawals(Request $request)
    {
        $query = Withdrawal::with('user');
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        /** @var \Illuminate\Pagination\LengthAwarePaginator $withdrawals */
        $withdrawals = $query->latest()->paginate(20);
        $withdrawals->setCollection($withdrawals->getCollection()->map(fn($w) => [
            'id' => $w->withdrawal_number,
            'name' => $w->user->name,
            'email' => $w->user->email,
            'amount' => $w->amount,
            'status' => $w->status,
            'method' => $w->method,
            'requested_date' => $w->created_at->format('Y-m-d'),
            'processed_date' => $w->processed_at?->format('Y-m-d'),
            'transaction_id' => $w->withdrawal_number,
        ]));
        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }
    /**
     * Wallet system overview
     */
    public function walletOverview()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_wallet_balance' => \App\Models\Wallet::sum('balance'),
                'total_earnings' => \App\Models\Wallet::sum('total_earned'),
                'pending_withdrawals' => Withdrawal::where('status', 'pending')->sum('amount'),
                'on_hold_funds' => 0, // Add if you have hold logic
            ],
        ]);
    }
    /**
     * List all user wallets
     */
    public function wallets(Request $request)
    {
        $query = \App\Models\Wallet::with(['user', 'user.customer', 'user.technician']);

        if ($request->has('role')) {
            $query->whereHas('user', fn($q) => $q->where('role', $request->role));
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $wallets */
        $wallets = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $wallets->through(fn($w) => [
                'id' => 'TXN' . str_pad($w->id, 5, '0', STR_PAD_LEFT),
                'name' => $w->user->name,
                'role' => $w->user->role,
                'current_balance' => $w->balance,
                'on_hold' => 0,
                'earnings' => $w->total_earned,
                'last_transaction' => $w->updated_at->format('Y-m-d'),
                'type' => $w->transactions()->latest()->first()?->type ?? 'N/A',
                'status' => 'active',
            ]),
        ]);
    }

    /**
     * Request statistics for charts
     */
    public function requestStats(Request $request)
    {
        $period = $request->period ?? 'month'; // week, month, year

        $requests = ServiceRequest::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->when($period === 'week', fn($q) => $q->where('created_at', '>=', now()->subWeek()))
            ->when($period === 'month', fn($q) => $q->where('created_at', '>=', now()->subMonth()))
            ->when($period === 'year', fn($q) => $q->where('created_at', '>=', now()->subYear()))
            ->groupBy('date')
            ->get();
        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }
    /**
     * Service type distribution (pie chart)
     */
    public function serviceDistribution()
    {
        $distribution = ServiceRequest::join('services', 'service_requests.service_id', '=', 'services.id')
            ->selectRaw('services.name as service, COUNT(*) as count')
            ->groupBy('services.name')
            ->get();
        return response()->json([
            'success' => true,
            'data' => $distribution,
        ]);
    }
    /**
     * Revenue breakdown by service
     */
    public function revenueByService()
    {
        $revenue = Payment::join('jobs', 'payments.job_id', '=', 'jobs.id')
            ->join('service_requests', 'jobs.service_request_id', '=', 'service_requests.id')
            ->join('services', 'service_requests.service_id', '=', 'services.id')
            ->selectRaw('services.name as service, SUM(payments.amount) as total')
            ->groupBy('services.name')
            ->get();
        return response()->json([
            'success' => true,
            'data' => $revenue,
        ]);
    }
    /**
     * Top performing technicians
     */
    public function topTechnicians()
    {
        $technicians = Technician::with('user:id,name')
            ->orderByDesc('average_rating')
            ->orderByDesc('total_jobs_completed')
            ->take(10)
            ->get(['id', 'user_id', 'average_rating', 'total_jobs_completed']);
        return response()->json([
            'success' => true,
            'data' => $technicians->map(fn($t) => [
                'name' => $t->user->name,
                'rating' => $t->average_rating,
                'requests' => $t->total_jobs_completed,
            ]),
        ]);
    }

    /**
     * Customer satisfaction level (average rating %)
     */
    public function customerSatisfaction()
    {
        $avgRating = Review::avg('rating') ?? 0;
        $totalReviews = Review::count();
        $satisfactionPercent = round(($avgRating / 5) * 100, 1);

        return response()->json([
            'success' => true,
            'data' => [
                'satisfaction_percent' => $satisfactionPercent,
                'average_rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
            ],
        ]);
    }

    /**
     * Requests breakdown - completed vs cancelled per month
     */
    public function requestsBreakdown()
    {
        $completed = ServiceRequest::where('status', 'assigned')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        $cancelled = ServiceRequest::where('status', 'cancelled')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        // Merge all months
        $months = $completed->keys()->merge($cancelled->keys())->unique()->sort();

        return response()->json([
            'success' => true,
            'data' => $months->map(fn($m) => [
                'month' => $m,
                'completed' => $completed[$m] ?? 0,
                'cancelled' => $cancelled[$m] ?? 0,
            ])->values(),
        ]);
    }

    /**
     * Service utilization map - requests with location
     */
    public function serviceUtilization()
    {
        $locations = ServiceRequest::select('latitude', 'longitude', 'city_id')
            ->with('city:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw('latitude, longitude, city_id, COUNT(*) as request_count')
            ->groupBy('latitude', 'longitude', 'city_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations->map(fn($l) => [
                'lat' => $l->latitude,
                'lng' => $l->longitude,
                'city' => $l->city->name ?? 'Unknown',
                'count' => $l->request_count,
            ]),
        ]);
    }

    /**
     * Show single user details (View button)
     * GET /admin/users/{id}
     */
    public function showUser($id)
    {
        $user = User::with(['customer.city', 'technician.category', 'wallet'])->findOrFail($id);

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'profile_image' => $user->profile_image
                ? asset('storage/' . $user->profile_image)
                : null,
            'email_verified_at' => $user->email_verified_at,
            'phone_verified_at' => $user->phone_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        // Customer-specific data
        if ($user->role === 'customer' && $user->customer) {
            $data['customer'] = [
                'address' => $user->customer->address,
                'city' => $user->customer->city->name ?? null,
                'city_id' => $user->customer->city_id,
                'latitude' => $user->customer->latitude,
                'longitude' => $user->customer->longitude,
                'average_rating' => $user->customer->average_rating,
                'total_requests' => ServiceRequest::where('customer_id', $user->customer->id)->count(),
                'total_jobs' => Job::where('customer_id', $user->customer->id)->count(),
                'completed_jobs' => Job::where('customer_id', $user->customer->id)->where('status', 'completed')->count(),
            ];
        }

        // Technician-specific data
        if ($user->role === 'technician' && $user->technician) {
            $data['technician'] = [
                'service_category' => $user->technician->category->name ?? null,
                'service_category_id' => $user->technician->service_category_id,
                'city' => $user->technician->city->name ?? null,
                'city_id' => $user->technician->city_id,
                'years_of_experience' => $user->technician->years_of_experience,
                'bio' => $user->technician->bio,
                'verification_status' => $user->technician->verification_status,
                'average_rating' => $user->technician->average_rating,
                'total_jobs_completed' => $user->technician->total_jobs_completed,
                'is_available' => $user->technician->is_available,
            ];
        }

        // Wallet data
        if ($user->wallet) {
            $data['wallet'] = [
                'balance' => $user->wallet->balance,
                'pending_balance' => $user->wallet->pending_balance,
                'total_earned' => $user->wallet->total_earned,
                'total_withdrawn' => $user->wallet->total_withdrawn,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Disable/Enable user (toggle is_active)
     * POST /admin/users/{id}/toggle-active
     */
    public function toggleUserActive($id)
    {
        $user = User::findOrFail($id);

        // Prevent admin from disabling themselves
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot disable your own account',
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        // If deactivating, revoke all tokens so they are logged out
        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => $user->is_active
                ? 'User activated successfully'
                : 'User disabled successfully',
            'data' => [
                'id' => $user->id,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Block user (soft delete + deactivate + revoke tokens)
     * POST /admin/users/{id}/block
     */
    public function blockUser($id)
    {
        $user = User::findOrFail($id);

        // Prevent admin from blocking themselves
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot block your own account',
            ], 403);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Deactivate and soft delete
        $user->is_active = false;
        $user->save();
        $user->delete(); // soft delete

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }
}
