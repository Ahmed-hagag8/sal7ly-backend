<?php
namespace App\Http\Controllers\v1\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\v1\Shared\ProfileController;
use App\Services\OtpService;

class AuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

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

    // ========== PHONE OTP ==========

    /**
     * Forgot password - send reset code via phone
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
        ]);

        $code = $this->otpService->send($request->phone);

        return response()->json([
            'success' => true,
            'message' => 'Reset code sent to your phone',
            'data' => array_filter([
                'code' => config('app.debug') ? $code : null,
                'expires_in' => config('services.otp.expiry_minutes', 10) . ' minutes',
            ]),
        ]);
    }

    /**
     * Reset password with phone code
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!$this->otpService->verify($request->phone, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code',
            ], 422);
        }

        // Update password
        $user = User::where('phone', $request->phone)->first();
        $user->password = Hash::make($request->password);
        $user->save();

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

        $code = $this->otpService->send($user->phone);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone',
            'data' => array_filter([
                'code' => config('app.debug') ? $code : null,
                'expires_in' => config('services.otp.expiry_minutes', 10) . ' minutes',
            ]),
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

        if (!$this->otpService->verify($user->phone, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 422);
        }

        // Mark phone as verified
        $user->phone_verified_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully',
        ]);
    }

    // ========== EMAIL OTP ==========

    /**
     * Send OTP for email verification (authenticated user)
     */
    public function sendEmailOtp(Request $request)
    {
        $user = $request->user();

        if (!$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'No email address on your account. Update your profile first.',
            ], 422);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified',
            ], 422);
        }

        $code = $this->otpService->sendToEmail($user->email, $user->name);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email',
            'data' => array_filter([
                'email' => $user->email,
                'code' => config('app.debug') ? $code : null,
                'expires_in' => config('services.otp.expiry_minutes', 10) . ' minutes',
            ]),
        ]);
    }

    /**
     * Verify email OTP (authenticated user)
     */
    public function verifyEmailOtp(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'No email address on your account',
            ], 422);
        }

        if (!$this->otpService->verifyEmail($user->email, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired email OTP',
            ], 422);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
        ]);
    }

    /**
     * Forgot password - send reset code via email
     */
    public function forgotPasswordEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        $code = $this->otpService->sendToEmail($request->email, $user->name);

        return response()->json([
            'success' => true,
            'message' => 'Reset code sent to your email',
            'data' => array_filter([
                'code' => config('app.debug') ? $code : null,
                'expires_in' => config('services.otp.expiry_minutes', 10) . ' minutes',
            ]),
        ]);
    }

    /**
     * Reset password with email code
     */
    public function resetPasswordEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!$this->otpService->verifyEmail($request->email, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code',
            ], 422);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }
}