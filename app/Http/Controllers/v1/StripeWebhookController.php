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
    public function handle(Request $request, StripeService $stripeService, PaymentService $paymentService)
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
                $paymentService->confirmStripePayment(
                    $paymentIntent->id,
                    $paymentIntent->latest_charge ?? null
                );
                Log::info('Stripe payment confirmed', [
                    'payment_intent_id' => $paymentIntent->id,
                ]);
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
