<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentService
{
    protected WalletService $walletService;
    protected float $commissionRate = 0.15; // 15% platform commission

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Process payment for completed job
     */
    public function processPayment(Job $job, string $paymentMethod = 'cash'): Payment
    {
        $amount = $job->final_price ?? $job->agreed_price;
        $commission = $amount * $this->commissionRate;
        $technicianEarnings = $amount - $commission;

        // Create payment record
        $payment = Payment::create([
            'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
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
            'earning',
            "Payment for Job #{$job->job_number}",
            'payment',
            $payment->id
        );

        return $payment;
    }
}
