<?php
namespace App\Http\Controllers\v1\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\v1\Shared\ProfileController;

class AuthController extends Controller
{
    /**
     * Login user and create token
     * 
     * WHY: Mobile/Web apps call this to get an authentication token.
     * The token proves "this user logged in successfully" for future requests.
     */
    public function login(Request $request)
    {
        // Step 1: Validate the input
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);
        // Step 2: Find user by phone
        $user = User::where('phone', $request->phone)->first();
        // Step 3: Check password
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }
        // Step 4: Check if user is active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'phone' => ['Your account has been deactivated.'],
            ]);
        }
        // Step 5: Create token
        // 'auth_token' is just a name to identify this token
        $token = $user->createToken('auth_token')->plainTextToken;
        // Step 6: Return user data + token
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
        ]);
    }
    /**
     * Logout user (revoke token)
     * 
     * WHY: When user logs out, we delete their token so it can't be used again.
     */
    public function logout(Request $request)
    {
        // Delete the current token
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
    /**
     * Get current user info
     * 
     * WHY: Mobile/Web apps call this to get the logged-in user's details.
     * Useful when app restarts and needs to restore user session.
     */
    public function me(Request $request)
    {

        return app(ProfileController::class)->show($request);

    }

    /**
     * Forgot password - send reset code
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
        ]);

        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete old codes for this phone
        \DB::table('password_resets')->where('phone', $request->phone)->delete();

        // Store new code (expires in 10 minutes)
        \DB::table('password_resets')->insert([
            'phone' => $request->phone,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // In production: send SMS with the code
        // For now: return code in response (dev only)
        return response()->json([
            'success' => true,
            'message' => 'Reset code sent to your phone',
            'data' => [
                'code' => $code, // Remove in production!
                'expires_in' => '10 minutes',
            ],
        ]);
    }

    /**
     * Reset password with code
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find the latest reset record
        $reset = \DB::table('password_resets')
            ->where('phone', $request->phone)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code',
            ], 422);
        }

        // Verify code
        if (!Hash::check($request->code, $reset->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reset code',
            ], 422);
        }

        // Update password
        $user = User::where('phone', $request->phone)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Mark code as used
        \DB::table('password_resets')->where('id', $reset->id)->update(['used' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }

    /**
     * Send OTP for phone verification
     */
    public function sendOtp(Request $request)
    {
        $user = $request->user();

        // Generate 6-digit OTP
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP (reuse password_resets table)
        \DB::table('password_resets')->where('phone', $user->phone)->delete();
        \DB::table('password_resets')->insert([
            'phone' => $user->phone,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // In production: send SMS
        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone',
            'data' => [
                'code' => $code, // Remove in production!
                'expires_in' => '5 minutes',
            ],
        ]);
    }

    /**
     * Verify phone OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        $reset = \DB::table('password_resets')
            ->where('phone', $user->phone)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$reset || !Hash::check($request->code, $reset->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 422);
        }

        // Mark phone as verified
        $user->phone_verified_at = now();
        $user->save();

        // Mark OTP as used
        \DB::table('password_resets')->where('id', $reset->id)->update(['used' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully',
        ]);
    }
}