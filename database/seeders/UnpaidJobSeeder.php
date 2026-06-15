<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnpaidJobSeeder extends Seeder
{
    public function run(): void
    {
        $customer1Id = DB::table('customers')->first()->id ?? 1;
        $tech1Id = DB::table('technicians')->first()->id ?? 1;
        $categoryId = DB::table('service_categories')->first()->id ?? 1;
        $cityId = DB::table('cities')->first()->id ?? 1;

        // Create a Service Request
        $reqId = DB::table('service_requests')->insertGetId([
            'request_number' => 'REQ-TEST-STRIPE',
            'customer_id' => $customer1Id,
            'category_id' => $categoryId,
            'city_id' => $cityId,
            'title' => 'Test Job for Stripe Payment',
            'description' => 'This job is completed but not paid yet.',
            'address' => 'Test Address',
            'status' => 'completed',
            'created_at' => Carbon::now()->subDays(1),
            'updated_at' => Carbon::now()->subDays(1),
        ]);

        // Create an accepted Offer
        $offerId = DB::table('job_offers')->insertGetId([
            'service_request_id' => $reqId,
            'technician_id' => $tech1Id,
            'offered_price' => 500.00,
            'status' => 'accepted',
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        // Create the Job in 'completed' status
        $jobId = DB::table('jobs')->insertGetId([
            'job_number' => 'JOB-TEST-STRIPE',
            'service_request_id' => $reqId,
            'job_offer_id' => $offerId,
            'customer_id' => $customer1Id,
            'technician_id' => $tech1Id,
            'agreed_price' => 500.00,
            'final_price' => 500.00,
            'status' => 'completed',
            'started_at' => Carbon::now()->subHours(1),
            'completed_at' => Carbon::now(),
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now(),
        ]);

        echo "✅ Unpaid Test Job created successfully! Job ID: " . $jobId . "\n";
    }
}
