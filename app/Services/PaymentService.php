<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * Process payment for completed job
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
}

