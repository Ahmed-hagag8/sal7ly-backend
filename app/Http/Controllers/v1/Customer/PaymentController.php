<?php

namespace App\Http\Controllers\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process payment for job
     *
     * For cash/wallet: instant completion (existing flow).
     * For card: creates a Stripe PaymentIntent and returns the client_secret
     * for the mobile app to confirm via Stripe SDK.
     */
    public function pay(Request $request, $jobId)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,wallet',
        ]);

        $customer = $request->user()->customer;

        $job = Job::where('customer_id', $customer->id)
            ->where('id', $jobId)
            ->where('status', 'completed')
            ->firstOrFail();

        // Check if already paid
        if ($job->payment) {
            return response()->json([
                'success' => false,
                'message' => 'Job already paid',
            ], 400);
        }

        // Card payment — async Stripe flow
        if ($request->payment_method === 'card') {
            $payment = $this->paymentService->createStripePayment($job);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated. Complete payment in the app.',
                'data' => [
                    'payment_number' => $payment->payment_number,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'stripe_client_secret' => $payment->stripe_client_secret,
                ],
            ]);
        }

        // Cash / Wallet — instant completion
        $payment = $this->paymentService->processPayment(
            $job,
            $request->payment_method
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment successful',
            'data' => [
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'commission' => $payment->commission_amount,
                'technician_earnings' => $payment->technician_earnings,
            ],
        ]);
    }

    /**
     * Check payment status for a job.
     *
     * Used by the mobile app to poll after initiating a Stripe card payment.
     * GET /customer/jobs/{id}/payment-status
     */
    public function status(Request $request, $jobId)
    {
        $customer = $request->user()->customer;

        $job = Job::where('customer_id', $customer->id)
            ->where('id', $jobId)
            ->firstOrFail();

        $payment = $job->payment;

        if (!$payment) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'unpaid',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment_number' => $payment->payment_number,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at,
            ],
        ]);
    }
}
