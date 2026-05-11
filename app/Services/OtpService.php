<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class OtpService
{
    // ========== PHONE OTP ==========

    /**
     * Generate, store, and dispatch OTP for a phone number
     *
     * @return string The plain OTP code (for debug response)
     */
    public function send(string $phone): string
    {
        $code = $this->generateCode();

        // Clean old codes
        DB::table('password_resets')->where('phone', $phone)->delete();

        // Store hashed code
        DB::table('password_resets')->insert([
            'phone'      => $phone,
            'email'      => null,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(config('services.otp.expiry_minutes', 10)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Dispatch via configured driver
        $this->dispatchPhone($phone, $code);

        return $code;
    }

    /**
     * Verify OTP code against stored hash (phone-based)
     */
    public function verify(string $phone, string $code): bool
    {
        $reset = DB::table('password_resets')
            ->where('phone', $phone)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$reset || !Hash::check($code, $reset->code)) {
            return false;
        }

        DB::table('password_resets')->where('id', $reset->id)->update(['used' => true]);
        return true;
    }

    // ========== EMAIL OTP ==========

    /**
     * Generate, store, and dispatch OTP for an email address
     *
     * @return string The plain OTP code (for debug response)
     */
    public function sendToEmail(string $email, string $userName = 'User'): string
    {
        $code = $this->generateCode();

        // Clean old codes for this email
        DB::table('password_resets')->where('email', $email)->delete();

        // Store hashed code
        DB::table('password_resets')->insert([
            'phone'      => null,
            'email'      => $email,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(config('services.otp.expiry_minutes', 10)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Dispatch via configured email driver
        $this->dispatchEmail($email, $code, $userName);

        return $code;
    }

    /**
     * Verify OTP code against stored hash (email-based)
     */
    public function verifyEmail(string $email, string $code): bool
    {
        $reset = DB::table('password_resets')
            ->where('email', $email)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$reset || !Hash::check($code, $reset->code)) {
            return false;
        }

        DB::table('password_resets')->where('id', $reset->id)->update(['used' => true]);
        return true;
    }

    // ========== HELPERS ==========

    /**
     * Generate a secure 6-digit OTP code
     */
    protected function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send phone OTP via configured driver (log for dev, twilio for production)
     */
    protected function dispatchPhone(string $phone, string $code): void
    {
        match (config('services.otp.driver')) {
            'twilio' => $this->sendViaTwilio($phone, $code),
            default  => Log::info("📱 OTP for [{$phone}]: {$code}"),
        };
    }

    /**
     * Send email OTP via configured driver (log for dev, smtp for production)
     */
    protected function dispatchEmail(string $email, string $code, string $userName = 'User'): void
    {
        $expiryMinutes = config('services.otp.expiry_minutes', 10);

        match (config('services.otp.email_driver', 'log')) {
            'smtp' => Mail::to($email)->send(new OtpMail($code, $userName, $expiryMinutes)),
            default => Log::info("📧 Email OTP for [{$email}]: {$code}"),
        };
    }

    /**
     * Send via Twilio (implement when ready for production)
     */
    protected function sendViaTwilio(string $phone, string $code): void
    {
        // TODO: Implement when ready for production
        // Twilio::message($phone, "Your Sal7ly code is: {$code}");
        Log::info("📱 OTP for [{$phone}] (Twilio placeholder): {$code}");
    }
}