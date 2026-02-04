<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Auth\CustomerRegisterRequest;
use App\Http\Requests\v1\Auth\TechnicianRegisterRequest;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Register a new customer
     * 
     * FLOW:
     * 1. Validate input (done by CustomerRegisterRequest)
     * 2. Create user with role 'customer'
     * 3. Create customer profile
     * 4. Create wallet (everyone needs a wallet)
     * 5. Generate auth token
     * 6. Return user data + token
     */
    public function customer(CustomerRegisterRequest $request)
    {
        // Use DB transaction to ensure all or nothing
        $result = DB::transaction(function () use ($request) {

            // Step 1: Create user
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'customer',
                'is_active' => true,
            ]);

            // Step 2: Create customer profile
            Customer::create([
                'user_id' => $user->id,
                'city_id' => $request->city_id,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            // Step 3: Create wallet
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
            ]);

            return $user;
        });

        // Generate token
        $token = $result->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully',
            'data' => [
                'user' => [
                    'id' => $result->id,
                    'name' => $result->name,
                    'email' => $result->email,
                    'phone' => $result->phone,
                    'role' => $result->role,
                ],
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Register a new technician
     * 
     * FLOW:
     * 1. Validate input (done by TechnicianRegisterRequest)
     * 2. Create user with role 'technician'
     * 3. Create technician profile (status = pending)
     * 4. Create wallet
     * 5. Generate auth token
     * 6. Return user data + token
     * 
     * NOTE: Technician starts as 'pending' and cannot accept jobs
     * until admin approves them (Day 10)
     */
    public function technician(TechnicianRegisterRequest $request)
    {
        $result = DB::transaction(function () use ($request) {

            // Step 1: Create user
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'technician',
                'is_active' => true,
            ]);

            // Step 2: Create technician profile (pending verification)
            Technician::create([
                'user_id' => $user->id,
                'city_id' => $request->city_id,
                'service_category_id' => $request->service_category_id,
                'years_of_experience' => $request->years_of_experience,
                'bio' => $request->bio,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'verification_status' => 'pending', // Must be verified by admin
                'is_available' => false, // Cannot accept jobs until approved
            ]);

            // Step 3: Create wallet
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
            ]);

            return $user;
        });

        $token = $result->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Technician registered successfully. Please wait for verification.',
            'data' => [
                'user' => [
                    'id' => $result->id,
                    'name' => $result->name,
                    'email' => $result->email,
                    'phone' => $result->phone,
                    'role' => $result->role,
                ],
                'verification_status' => 'pending',
                'token' => $token,
            ],
        ], 201);
    }
}
