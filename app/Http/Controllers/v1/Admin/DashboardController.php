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


}
