<?php

namespace App\Http\Controllers\v1\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Get wallet summary
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet->balance,
                'pending_balance' => $wallet->pending_balance,
                'total_earned' => $wallet->total_earned,
                'total_withdrawn' => $wallet->total_withdrawn,
                'available_for_withdrawal' => $wallet->balance,
            ],
        ]);
    }

    /**
     * Get transaction history
     */
    public function transactions(Request $request)
    {
        $wallet = $request->user()->wallet;

        $transactions = $wallet->transactions()
            ->latest('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions->through(fn($t) => [
                'id' => $t->id,
                'transaction_number' => $t->transaction_number,
                'type' => $t->type,
                'amount' => $t->amount,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'created_at' => $t->created_at,
            ]),
        ]);
    }
}
