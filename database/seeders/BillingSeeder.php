<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\UniqueNumberGenerator;
use Carbon\Carbon;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        // Assume TestUsersSeeder has already run, giving us:
        // Customer IDs: 1, 2
        // Technician IDs: 1, 2 (Approved)
        $customer1Id = DB::table('customers')->first()->id ?? 1;
        $customer2Id = DB::table('customers')->orderBy('id', 'desc')->first()->id ?? 2;
        $tech1Id = DB::table('technicians')->first()->id ?? 1;
        
        $customer1UserId = DB::table('customers')->where('id', $customer1Id)->first()->user_id;
        $tech1UserId = DB::table('technicians')->where('id', $tech1Id)->first()->user_id;

        $categoryId = DB::table('service_categories')->first()->id ?? 1;
        $cityId = DB::table('cities')->first()->id ?? 1;

        // Generate a few jobs to attach payments to
        for ($i = 1; $i <= 5; $i++) {
            $reqId = DB::table('service_requests')->insertGetId([
                'request_number' => 'REQ-BILL' . rand(1000, 9999),
                'customer_id' => $customer1Id,
                'category_id' => $categoryId,
                'city_id' => $cityId,
                'title' => 'Mock Billing Request ' . $i,
                'description' => 'Fixing issues ' . $i,
                'address' => 'Test Address',
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(10 - $i),
                'updated_at' => Carbon::now()->subDays(10 - $i),
            ]);

            $offerId = DB::table('job_offers')->insertGetId([
                'service_request_id' => $reqId,
                'technician_id' => $tech1Id,
                'offered_price' => 150.00 + ($i * 50),
                'status' => 'accepted',
                'created_at' => Carbon::now()->subDays(10 - $i),
                'updated_at' => Carbon::now()->subDays(10 - $i),
            ]);

            $jobId = DB::table('jobs')->insertGetId([
                'job_number' => 'JOB-BILL' . rand(1000, 9999),
                'service_request_id' => $reqId,
                'job_offer_id' => $offerId,
                'customer_id' => $customer1Id,
                'technician_id' => $tech1Id,
                'agreed_price' => 150.00 + ($i * 50),
                'final_price' => 150.00 + ($i * 50),
                'status' => 'completed',
                'started_at' => Carbon::now()->subDays(10 - $i)->addHours(1),
                'completed_at' => Carbon::now()->subDays(10 - $i)->addHours(3),
                'created_at' => Carbon::now()->subDays(10 - $i),
                'updated_at' => Carbon::now()->subDays(10 - $i),
            ]);

            $amount = 150.00 + ($i * 50);
            $commission = $amount * 0.10;
            $earnings = $amount - $commission;

            // Create Payment
            DB::table('payments')->insert([
                'payment_number' => 'PAY-BILL' . rand(1000, 9999),
                'job_id' => $jobId,
                'customer_id' => $customer1Id,
                'technician_id' => $tech1Id,
                'amount' => $amount,
                'commission_amount' => $commission,
                'technician_earnings' => $earnings,
                'payment_method' => $i % 2 == 0 ? 'cash' : 'wallet',
                'status' => 'completed',
                'paid_at' => Carbon::now()->subDays(10 - $i)->addHours(4),
                'created_at' => Carbon::now()->subDays(10 - $i)->addHours(4),
                'updated_at' => Carbon::now()->subDays(10 - $i)->addHours(4),
            ]);

            // If wallet payment, add customer transaction
            if ($i % 2 != 0) {
                $customerWalletId = DB::table('wallets')->where('user_id', $customer1UserId)->first()->id ?? null;
                if ($customerWalletId) {
                    DB::table('transactions')->insert([
                        'transaction_number' => 'TXN-CUST' . rand(1000, 9999),
                        'wallet_id' => $customerWalletId,
                        'type' => 'debit',
                        'amount' => $amount,
                        'balance_before' => 1000,
                        'balance_after' => 1000 - $amount,
                        'description' => 'Payment for Job ' . $jobId,
                        'created_at' => Carbon::now()->subDays(10 - $i)->addHours(4),
                    ]);
                }
            }

            // Always add technician earning transaction
            $techWalletId = DB::table('wallets')->where('user_id', $tech1UserId)->first()->id ?? null;
            if ($techWalletId) {
                DB::table('transactions')->insert([
                    'transaction_number' => 'TXN-TECH' . rand(1000, 9999),
                    'wallet_id' => $techWalletId,
                    'type' => 'credit',
                    'amount' => $earnings,
                    'balance_before' => 1000,
                    'balance_after' => 1000 + $earnings,
                    'description' => 'Earnings for Job ' . $jobId,
                    'created_at' => Carbon::now()->subDays(10 - $i)->addHours(4),
                ]);
            }
        }

        // Mock some withdrawals
        DB::table('withdrawals')->insert([
            [
                'withdrawal_number' => 'WTH-001',
                'user_id' => $tech1UserId,
                'processed_by' => null,
                'amount' => 200.00,
                'method' => 'bank_transfer',
                'status' => 'pending',
                'processed_at' => null,
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'withdrawal_number' => 'WTH-002',
                'user_id' => $tech1UserId,
                'processed_by' => 1, // assuming user_id=1 is admin
                'amount' => 500.00,
                'method' => 'vodafone_cash',
                'status' => 'approved',
                'processed_at' => Carbon::now()->subDays(2),
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'withdrawal_number' => 'WTH-003',
                'user_id' => $tech1UserId,
                'processed_by' => 1,
                'amount' => 150.00,
                'method' => 'bank_transfer',
                'status' => 'rejected',
                'processed_at' => Carbon::now()->subDays(1),
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ]
        ]);

        echo "✅ Billing data mocked successfully (Jobs, Payments, Transactions, Withdrawals).\n";
    }
}
