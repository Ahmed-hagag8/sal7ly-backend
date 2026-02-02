<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Get service category IDs
        $plumbingCategoryId = DB::table('service_categories')->where('name', 'Plumbing')->first()->id;
        $electricalCategoryId = DB::table('service_categories')->where('name', 'Electrical')->first()->id;

        // Get a city ID
        $cityId = DB::table('cities')->where('name', 'Cairo')->first()?->id ?? 1;

        // ===== Create Test Customers =====

        // Customer 1
        $customer1UserId = DB::table('users')->insertGetId([
            'name' => 'Ahmed Hassan',
            'email' => 'customer1@test.com',
            'phone' => '01111111111',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => 1,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'user_id' => $customer1UserId,
            'address' => '123 Tahrir Street, Cairo',
            'city_id' => $cityId,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallets')->insert([
            'user_id' => $customer1UserId,
            'balance' => 500.00,
            'pending_balance' => 0.00,
            'total_earned' => 0.00,
            'total_withdrawn' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Customer 2
        $customer2UserId = DB::table('users')->insertGetId([
            'name' => 'Sara Mohamed',
            'email' => 'customer2@test.com',
            'phone' => '01222222222',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => 1,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'user_id' => $customer2UserId,
            'address' => '456 Nasr City, Cairo',
            'city_id' => $cityId,
            'latitude' => 30.0626,
            'longitude' => 31.3549,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallets')->insert([
            'user_id' => $customer2UserId,
            'balance' => 300.00,
            'pending_balance' => 0.00,
            'total_earned' => 0.00,
            'total_withdrawn' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ===== Create Test Technicians =====

        // Technician 1 - Plumber (APPROVED)
        $tech1UserId = DB::table('users')->insertGetId([
            'name' => 'Mohamed Ali',
            'email' => 'tech1@test.com',
            'phone' => '01333333333',
            'password' => Hash::make('password'),
            'role' => 'technician',
            'is_active' => 1,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('technicians')->insert([
            'user_id' => $tech1UserId,
            'bio' => 'Experienced plumber with 10 years of experience',
            'years_of_experience' => 10,
            'service_category_id' => $plumbingCategoryId,
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => 1, // Admin ID
            'average_rating' => 4.5,
            'total_jobs_completed' => 45,
            'city_id' => $cityId,
            'latitude' => 30.0500,
            'longitude' => 31.2400,
            'is_available' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallets')->insert([
            'user_id' => $tech1UserId,
            'balance' => 2500.00,
            'pending_balance' => 500.00,
            'total_earned' => 15000.00,
            'total_withdrawn' => 12000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Technician 2 - Electrician (APPROVED)
        $tech2UserId = DB::table('users')->insertGetId([
            'name' => 'Mahmoud Ibrahim',
            'email' => 'tech2@test.com',
            'phone' => '01444444444',
            'password' => Hash::make('password'),
            'role' => 'technician',
            'is_active' => 1,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('technicians')->insert([
            'user_id' => $tech2UserId,
            'bio' => 'Professional electrician specializing in home and commercial electrical work',
            'years_of_experience' => 8,
            'service_category_id' => $electricalCategoryId,
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => 1,
            'average_rating' => 4.8,
            'total_jobs_completed' => 62,
            'city_id' => $cityId,
            'latitude' => 30.0700,
            'longitude' => 31.2500,
            'is_available' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallets')->insert([
            'user_id' => $tech2UserId,
            'balance' => 3200.00,
            'pending_balance' => 800.00,
            'total_earned' => 22000.00,
            'total_withdrawn' => 18000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Technician 3 - Pending Verification
        $tech3UserId = DB::table('users')->insertGetId([
            'name' => 'Khaled Samir',
            'email' => 'tech3@test.com',
            'phone' => '01555555555',
            'password' => Hash::make('password'),
            'role' => 'technician',
            'is_active' => 1,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('technicians')->insert([
            'user_id' => $tech3UserId,
            'bio' => 'New carpenter looking to get verified',
            'years_of_experience' => 3,
            'service_category_id' => DB::table('service_categories')->where('name', 'Carpentry')->first()->id,
            'verification_status' => 'pending',
            'verified_at' => null,
            'verified_by' => null,
            'average_rating' => 0.00,
            'total_jobs_completed' => 0,
            'city_id' => $cityId,
            'latitude' => 30.0600,
            'longitude' => 31.2450,
            'is_available' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallets')->insert([
            'user_id' => $tech3UserId,
            'balance' => 0.00,
            'pending_balance' => 0.00,
            'total_earned' => 0.00,
            'total_withdrawn' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Test users created:\n";
        echo "   Customers: customer1@test.com, customer2@test.com\n";
        echo "   Technicians (approved): tech1@test.com, tech2@test.com\n";
        echo "   Technicians (pending): tech3@test.com\n";
        echo "   All passwords: password\n";
    }
}