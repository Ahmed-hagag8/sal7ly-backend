<?php

namespace App\Http\Controllers\v1\Technician;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    /**
     * Request withdrawal
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
            'data' => $withdrawals,
        ]);
    }
}
