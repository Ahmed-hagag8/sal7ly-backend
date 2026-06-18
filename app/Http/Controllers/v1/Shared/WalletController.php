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

        $transactions->setCollection($transactions->getCollection()->map(fn($t) => [
            'id' => $t->id,
            'transaction_number' => $t->transaction_number,
            'type' => $t->type,
            'amount' => $t->amount,
            'balance_after' => $t->balance_after,
            'description' => $t->description,
            'created_at' => $t->created_at,
        ]));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Initiate wallet funding via Stripe
     */
    public function fund(Request $request, \App\Services\StripeService $stripeService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50',
        ]);

        $user = $request->user();

        try {
            $paymentIntent = $stripeService->createPaymentIntent($request->amount, [
                'type' => 'wallet_fund',
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated',
                'data' => [
                    'amount' => $request->amount,
                    'status' => 'pending',
                    'payment_intent_id' => $paymentIntent->id,
                    'stripe_client_secret' => $paymentIntent->client_secret,
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Wallet funding failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate funding. Please try again.',
            ], 500);
        }
    }

    /**
     * Check the status of a wallet funding attempt
     */
    public function fundStatus(Request $request, $intentId)
    {
        $wallet = $request->user()->wallet;

        $transaction = \App\Models\Transaction::where('wallet_id', $wallet->id)
            ->where('reference_type', 'stripe_topup')
            ->where('description', 'LIKE', '%' . $intentId . '%')
            ->first();

        if ($transaction) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                    'amount' => $transaction->amount,
                    'transaction_number' => $transaction->transaction_number,
                    'paid_at' => $transaction->created_at,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'pending',
            ],
        ]);
    }
}
