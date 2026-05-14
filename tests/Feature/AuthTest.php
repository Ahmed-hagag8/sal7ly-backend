<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\Wallet;
use App\Models\City;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ==================== LOGIN ====================

    public function test_login_with_email_success()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@sal7ly.com',
            'phone' => '01000000001',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $user->email_verified_at = now();
        $user->save();

        $response = $this->postJson('/api/login', [
            'email' => 'test@sal7ly.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['id', 'name', 'email', 'phone', 'role'], 'token'],
            ])
            ->assertJson(['success' => true, 'message' => 'Login successful']);
    }

    public function test_login_with_phone_success()
    {
        User::create([
            'name' => 'Phone User',
            'email' => 'phone@sal7ly.com',
            'phone' => '01000000002',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'phone' => '01000000002',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_login_with_wrong_password()
    {
        User::create([
            'name' => 'Test User',
            'email' => 'wrong@sal7ly.com',
            'phone' => '01000000003',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'wrong@sal7ly.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_nonexistent_email()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@sal7ly.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_requires_email_or_phone()
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_requires_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@sal7ly.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_deactivated_user_rejected()
    {
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@sal7ly.com',
            'phone' => '01000000004',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@sal7ly.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_admin_unverified_email_rejected()
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@sal7ly.com',
            'phone' => '01000000005',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            // email_verified_at is null
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@sal7ly.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['Your email is not verified. Please verify your email first.']);
    }

    public function test_login_admin_phone_skips_email_verification()
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin2@sal7ly.com',
            'phone' => '01000000006',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            // email_verified_at is null - but logging in with phone
        ]);

        $response = $this->postJson('/api/login', [
            'phone' => '01000000006',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ==================== LOGOUT ====================

    public function test_logout_success()
    {
        $user = User::create([
            'name' => 'Logout User',
            'email' => 'logout@sal7ly.com',
            'phone' => '01000000010',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Logged out successfully']);
    }

    public function test_logout_unauthenticated()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    // ==================== ME ====================

    public function test_me_returns_profile()
    {
        $user = User::create([
            'name' => 'Me User',
            'email' => 'me@sal7ly.com',
            'phone' => '01000000011',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);
        $city = City::create(['name' => 'Cairo', 'is_active' => true]);
        Customer::create([
            'user_id' => $user->id,
            'city_id' => $city->id,
            'address' => '123 Test St',
        ]);
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 100,
            'pending_balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
        ]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'email', 'phone', 'role']]);
    }

    public function test_me_unauthenticated()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    // ==================== FORGOT PASSWORD (Phone) ====================

    public function test_forgot_password_phone_success()
    {
        User::create([
            'name' => 'Forgot User',
            'email' => 'forgot@sal7ly.com',
            'phone' => '01000000020',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'phone' => '01000000020',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Reset code sent to your phone']);
    }

    public function test_forgot_password_phone_nonexistent()
    {
        $response = $this->postJson('/api/forgot-password', [
            'phone' => '09999999999',
        ]);

        $response->assertStatus(422);
    }

    // ==================== FORGOT PASSWORD (Email) ====================

    public function test_forgot_password_email_success()
    {
        User::create([
            'name' => 'Forgot Email User',
            'email' => 'forgotemail@sal7ly.com',
            'phone' => '01000000021',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/forgot-password-email', [
            'email' => 'forgotemail@sal7ly.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Reset code sent to your email']);
    }

    public function test_forgot_password_email_nonexistent()
    {
        $response = $this->postJson('/api/forgot-password-email', [
            'email' => 'nonexistent@sal7ly.com',
        ]);

        $response->assertStatus(422);
    }

    // ==================== RESET PASSWORD (Phone) ====================

    public function test_reset_password_phone_invalid_code()
    {
        User::create([
            'name' => 'Reset User',
            'email' => 'reset@sal7ly.com',
            'phone' => '01000000022',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/reset-password', [
            'phone' => '01000000022',
            'code' => '000000',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(422);
    }

    // ==================== RESET PASSWORD (Email) ====================

    public function test_reset_password_email_invalid_code()
    {
        User::create([
            'name' => 'Reset Email User',
            'email' => 'resetemail@sal7ly.com',
            'phone' => '01000000023',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/reset-password-email', [
            'email' => 'resetemail@sal7ly.com',
            'code' => '000000',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(422);
    }

    // ==================== SEND OTP (Phone - authenticated) ====================

    public function test_send_otp_authenticated()
    {
        $user = User::create([
            'name' => 'OTP User',
            'email' => 'otp@sal7ly.com',
            'phone' => '01000000030',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/send-otp');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'OTP sent to your phone']);
    }

    public function test_send_otp_unauthenticated()
    {
        $response = $this->postJson('/api/send-otp');

        $response->assertStatus(401);
    }

    // ==================== VERIFY OTP (Phone) ====================

    public function test_verify_otp_invalid_code()
    {
        $user = User::create([
            'name' => 'Verify User',
            'email' => 'verify@sal7ly.com',
            'phone' => '01000000031',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/verify-otp', [
            'code' => '000000',
        ]);

        $response->assertStatus(422);
    }

    // ==================== SEND EMAIL OTP ====================

    public function test_send_email_otp_no_email()
    {
        $user = User::create([
            'name' => 'No Email User',
            'email' => null,
            'phone' => '01000000032',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/send-email-otp');

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_send_email_otp_already_verified()
    {
        $user = User::create([
            'name' => 'Verified Email User',
            'email' => 'verified@sal7ly.com',
            'phone' => '01000000033',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);
        $user->email_verified_at = now();
        $user->save();

        $response = $this->actingAs($user)->postJson('/api/send-email-otp');

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Email is already verified']);
    }

    public function test_send_email_otp_success()
    {
        $user = User::create([
            'name' => 'Email OTP User',
            'email' => 'emailotp@sal7ly.com',
            'phone' => '01000000034',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/send-email-otp');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'OTP sent to your email']);
    }

    // ==================== VERIFY EMAIL OTP ====================

    public function test_verify_email_otp_no_email()
    {
        $user = User::create([
            'name' => 'No Email',
            'email' => null,
            'phone' => '01000000035',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/verify-email-otp', [
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_verify_email_otp_invalid_code()
    {
        $user = User::create([
            'name' => 'Verify Email User',
            'email' => 'verifyemail@sal7ly.com',
            'phone' => '01000000036',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/verify-email-otp', [
            'code' => '000000',
        ]);

        $response->assertStatus(422);
    }
}
