<?php

namespace App\Http\Controllers\v1\Shared;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Get current user's full profile
     * 
     * WHY: Mobile app needs complete user data including role-specific info
     * Customer gets: address, city
     * Technician gets: category, bio, rating, verification status
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Base user data
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'profile_image' => $user->profile_image
                ? asset('storage/' . $user->profile_image)
                : null,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
        ];

        // Add role-specific data
        if ($user->role === 'customer') {
            $customer = $user->customer;
            if ($customer) {
                $data['customer'] = [
                    'address' => $customer->address,
                    'city_id' => $customer->city_id,
                    'city_name' => $customer->city->name ?? null,
                    'latitude' => $customer->latitude,
                    'longitude' => $customer->longitude,
                    'average_rating' => $customer->average_rating,
                ];
            }
        } elseif ($user->role === 'technician') {
            $technician = $user->technician;
            if ($technician) {
                $data['technician'] = [
                    'service_category_id' => $technician->service_category_id,
                    'service_category_name' => $technician->category->name ?? null,
                    'city_id' => $technician->city_id,
                    'city_name' => $technician->city->name ?? null,
                    'years_of_experience' => $technician->years_of_experience,
                    'bio' => $technician->bio,
                    'verification_status' => $technician->verification_status,
                    'average_rating' => $technician->average_rating,
                    'total_jobs_completed' => $technician->total_jobs_completed,
                    'is_available' => $technician->is_available,
                ];
            }
        }

        // Add wallet balance
        $wallet = $user->wallet;
        if ($wallet) {
            $data['wallet'] = [
                'balance' => $wallet->balance,
                'pending_balance' => $wallet->pending_balance,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Update user profile
     * 
     * WHY: Users may need to change name, email, address, etc.
     * We validate based on role - customer vs technician fields
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Base validation rules
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ];

        // Add role-specific rules
        if ($user->role === 'customer') {
            $rules['address'] = 'sometimes|nullable|string|max:500';
            $rules['city_id'] = 'sometimes|exists:cities,id';
            $rules['latitude'] = 'sometimes|nullable|numeric|between:-90,90';
            $rules['longitude'] = 'sometimes|nullable|numeric|between:-180,180';
        } elseif ($user->role === 'technician') {
            $rules['bio'] = 'sometimes|nullable|string|max:1000';
            $rules['years_of_experience'] = 'sometimes|nullable|integer|min:0|max:50';
            $rules['city_id'] = 'sometimes|exists:cities,id';
            $rules['is_available'] = 'sometimes|boolean';
            $rules['latitude'] = 'sometimes|nullable|numeric|between:-90,90';
            $rules['longitude'] = 'sometimes|nullable|numeric|between:-180,180';
        }

        $validated = $request->validate($rules);

        // Update user base fields
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        $user->save();

        // Update role-specific profile
        if ($user->role === 'customer' && $user->customer) {
            $customerFields = array_intersect_key($validated, array_flip([
                'address',
                'city_id',
                'latitude',
                'longitude'
            ]));
            if (!empty($customerFields)) {
                $user->customer->update($customerFields);
            }
        } elseif ($user->role === 'technician' && $user->technician) {
            $technicianFields = array_intersect_key($validated, array_flip([
                'bio',
                'years_of_experience',
                'city_id',
                'is_available',
                'latitude',
                'longitude'
            ]));
            if (!empty($technicianFields)) {
                $user->technician->update($technicianFields);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Change email and password
     */
    public function changeCredentials(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Credentials updated successfully',
        ]);
    }

    /**
     * Upload profile image
     * 
     * WHY: Profile photos build trust between customers and technicians
     * We store in public/storage and return the URL
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        $user = $request->user();

        // Delete old image if exists
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // Store new image
        $path = $request->file('image')->store('profile_images', 'public');

        // Update user
        $user->profile_image = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile image uploaded successfully',
            'data' => [
                'profile_image' => asset('storage/' . $path),
            ],
        ]);
    }
}
