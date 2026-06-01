<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    /**
     * Request withdrawal
     *
     * SCALE-04: Holds funds in pending_balance on request so the
     * technician cannot spend the same money while awaiting approval.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50', // Minimum 50 EGP
            'method' => 'required|in:bank_transfer,vodafone_cash,instapay',
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if ($wallet->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
            ], 400);
        }

        // Check for pending withdrawals
        $pending = Withdrawal::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return response()->json([
                'success' => false,
                'message' => 'You have a pending withdrawal request',
            ], 400);
        }

        // Hold the funds so they can't be spent while awaiting approval
        app(WalletService::class)->holdFunds($wallet, (float) $request->amount);

        $withdrawal = Withdrawal::create([
            'withdrawal_number' => 'WD-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'amount' => $request->amount,
            'method' => $request->input('method'),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted',
            'data' => [
                'withdrawal_number' => $withdrawal->withdrawal_number,
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status,
            ],
        ], 201);
    }


    /**
     * List my withdrawals
     */
    public function index(Request $request)
    {
        $withdrawals = Withdrawal::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => collect($withdrawals->items())->map(fn($w) => [
                'id' => $w->id,
                'withdrawal_number' => $w->withdrawal_number,
                'amount' => $w->amount,
                'method' => $w->method,
                'status' => $w->status,
                'created_at' => $w->created_at,
                'processed_at' => $w->processed_at,
            ]),
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
        ]);
    }
}
