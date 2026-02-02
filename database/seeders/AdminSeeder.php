<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Admin Sal7ly',
            'email' => 'admin@sal7ly.com',
            'phone' => '01000000000',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => 1,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create wallet for admin
        DB::table('wallets')->insert([
            'user_id' => $userId,
            'balance' => 0.00,
            'pending_balance' => 0.00,
            'total_earned' => 0.00,
            'total_withdrawn' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Admin created: admin@sal7ly.com / password\n";
    }
}