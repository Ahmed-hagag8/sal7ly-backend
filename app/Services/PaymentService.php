<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Payment;
use App\Exceptions\PaymentFailedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\UniqueNumberGenerator;

class PaymentService
{
    protected WalletService $walletService;
    protected float $commissionRate;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
        $this->commissionRate = config('sal7ly.commission_rate', 0.15);
    }

    /**
     * Process payment for completed job (cash / wallet — instant completion)
     *
     * Wrapped in a DB transaction so that if the wallet credit fails,
     * the payment record is rolled back — preventing phantom payments.
     */
    public function processPayment(Job $job, string $paymentMethod = 'cash'): Payment
    {
        return DB::transaction(function () use ($job, $paymentMethod) {
            $amount = $job->final_price ?? $job->agreed_price;
            $commission = $amount * $this->commissionRate;
            $technicianEarnings = $amount - $commission;

            // Create payment record
            $payment = Payment::create([
                'payment_number' => UniqueNumberGenerator::generate('PAY-', 'payments', 'payment_number'),
                'job_id' => $job->id,
                'customer_id' => $job->customer_id,
                'technician_id' => $job->technician_id,
                'amount' => $amount,
                'commission_amount' => $commission,
                'technician_earnings' => $technicianEarnings,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Credit technician wallet
            $technician = $job->technician;
            $this->walletService->credit(
                $technician->user->wallet,
                $technicianEarnings,
                "Payment for Job #{$job->job_number}",
                'payment',
                $payment->id
            );

            return $payment;
        });
    }

    /**
     * Create a Stripe card payment (async — requires client-side confirmation).
     *
     * Creates a PaymentIntent via Stripe, stores the client_secret,
     * and returns the payment in 'requires_payment' status.
     * The mobile app uses the client_secret to confirm payment via Stripe SDK.
     */
    public function createStripePayment(Job $job): Payment
    {
        $amount = $job->final_price ?? $job->agreed_price;
        $commission = $amount * $this->commissionRate;
        $technicianEarnings = $amount - $commission;

        // Create payment record first (in requires_payment state)
        $payment = Payment::create([
            'payment_number' => UniqueNumberGenerator::generate('PAY-', 'payments', 'payment_number'),
            'job_id' => $job->id,
            'customer_id' => $job->customer_id,
            'technician_id' => $job->technician_id,
            'amount' => $amount,
            'commission_amount' => $commission,
            'technician_earnings' => $technicianEarnings,
            'payment_method' => 'card',
            'status' => 'requires_payment',
        ]);

        try {
            // Create Stripe PaymentIntent
            $stripeService = app(StripeService::class);
            $paymentIntent = $stripeService->createPaymentIntent($amount, [
                'job_id'         => $job->id,
                'payment_id'     => $payment->id,
                'payment_number' => $payment->payment_number,
            ]);

            // Store Stripe IDs on the payment
            $payment->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_client_secret'     => $paymentIntent->client_secret,
            ]);

            return $payment;
        } catch (\Exception $e) {
            // If Stripe fails, delete the payment record and throw
            $payment->delete();
            Log::error('Stripe PaymentIntent creation failed', [
                'job_id' => $job->id,
                'error'  => $e->getMessage(),
            ]);
            throw new PaymentFailedException('Failed to initiate card payment. Please try again.');
        }
    }

    /**
     * Confirm a Stripe payment after webhook notification.
     *
     * Marks the payment as completed, records the charge ID,
     * and credits the technician's wallet.
     */
    public function confirmStripePayment(string $paymentIntentId, ?string $chargeId = null): ?Payment
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
            ->where('status', 'requires_payment')
            ->first();

        if (!$payment) {
            Log::warning('Stripe webhook: payment not found or already processed', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            return null;
        }

        return DB::transaction(function () use ($payment, $chargeId) {
            $payment->update([
                'status'           => 'completed',
                'paid_at'          => now(),
                'stripe_charge_id' => $chargeId,
            ]);

            // Credit technician wallet
            $job = $payment->job;
            $technician = $job->technician;
            $this->walletService->credit(
                $technician->user->wallet,
                $payment->technician_earnings,
                "Payment for Job #{$job->job_number}",
                'payment',
                $payment->id
            );

            // Notify technician
            \App\Services\NotificationService::send(
                $technician->user_id,
                'payment_received',
                'Payment Received!',
                "Payment of {$payment->amount} EGP received for Job #{$job->job_number}"
            );

            return $payment;
        });
    }

    /**
     * Mark a Stripe payment as failed after webhook notification.
     */
    public function failStripePayment(string $paymentIntentId): ?Payment
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
            ->where('status', 'requires_payment')
            ->first();

        if (!$payment) {
            return null;
        }

        $payment->update(['status' => 'failed']);

        return $payment;
    }
}
