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
}
