<?php
namespace App\Http\Controllers\v1\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
            ],
        ]);
    }
}