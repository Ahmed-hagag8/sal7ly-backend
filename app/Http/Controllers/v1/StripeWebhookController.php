<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     *
     * This endpoint is PUBLIC (no auth:sanctum) — Stripe cannot authenticate.
     * Security is enforced by verifying the Stripe-Signature header.
     */
    public function handle(Request $request, StripeService $stripeService, PaymentService $paymentService, \App\Services\WalletService $walletService)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (!$sigHeader) {
            return response()->json(['error' => 'Missing Stripe-Signature header'], 400);
        }

        try {
            $event = $stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Webhook error'], 400);
        }

        // Handle event types
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                
                // Check if this is a wallet top-up
                if (isset($paymentIntent->metadata->type) && $paymentIntent->metadata->type === 'wallet_fund') {
                    $userId = $paymentIntent->metadata->user_id ?? null;
                    if ($userId) {
                        $user = \App\Models\User::find($userId);
                        if ($user && $user->wallet) {
                            // Stripe amounts are in piasters, so divide by 100
                            $amount = $paymentIntent->amount / 100;
                            
                            // Check if transaction already exists to avoid double crediting
                            $exists = \App\Models\Transaction::where('reference_type', 'stripe_topup')
                                ->where('description', 'LIKE', '%' . $paymentIntent->id . '%')
                                ->exists();
                                
                            if (!$exists) {
                                $walletService->credit(
                                    $user->wallet,
                                    $amount,
                                    'Wallet Top-Up via Card (Stripe: ' . $paymentIntent->id . ')',
                                    'stripe_topup',
                                    null
                                );
                                Log::info('Stripe wallet fund confirmed', [
                                    'payment_intent_id' => $paymentIntent->id,
                                    'user_id' => $userId,
                                    'amount' => $amount
                                ]);
                            }
                        }
                    }
                } else {
                    // Regular job payment
                    $paymentService->confirmStripePayment(
                        $paymentIntent->id,
                        $paymentIntent->latest_charge ?? null
                    );
                    Log::info('Stripe payment confirmed', [
                        'payment_intent_id' => $paymentIntent->id,
                    ]);
                }
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $paymentService->failStripePayment($paymentIntent->id);
                Log::warning('Stripe payment failed', [
                    'payment_intent_id' => $paymentIntent->id,
                    'error' => $paymentIntent->last_payment_error?->message ?? 'Unknown',
                ]);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
                break;
        }

        return response()->json(['received' => true], 200);
    }
}
