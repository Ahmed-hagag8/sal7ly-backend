<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate, store, and dispatch OTP for a phone number
     *
     * @return string The plain OTP code (for debug response)
     */
    public function send(string $phone): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Clean old codes
        DB::table('password_resets')->where('phone', $phone)->delete();

        // Store hashed code
        DB::table('password_resets')->insert([
            'phone'      => $phone,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(config('services.otp.expiry_minutes', 10)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Dispatch via configured driver
        $this->dispatch($phone, $code);

        return $code;
    }

    /**
     * Verify OTP code against stored hash
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

    /**
     * Send OTP via configured driver (log for dev, twilio for production)
     */
    protected function dispatch(string $phone, string $code): void
    {
        match (config('services.otp.driver')) {
            'twilio' => $this->sendViaTwilio($phone, $code),
            default  => Log::info("📱 OTP for [{$phone}]: {$code}"),
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