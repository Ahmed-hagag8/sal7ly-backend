<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
     * Send phone OTP via configured driver (log for dev, ultramsg for production)
     */
    protected function dispatchPhone(string $phone, string $code): void
    {
        match (config('services.otp.driver')) {
            'ultramsg' => $this->sendViaUltramsg($phone, $code),
            default    => Log::info("📱 OTP for [{$phone}]: {$code}"),
        };
    }

    /**
     * Send email OTP via configured driver (log for dev, smtp for production)
     */
    protected function dispatchEmail(string $email, string $code, string $userName = 'User'): void
    {
        $expiryMinutes = config('services.otp.expiry_minutes', 10);

        match (config('services.otp.email_driver', 'log')) {
            'smtp'      => Mail::to($email)->send(new OtpMail($code, $userName, $expiryMinutes)),
            'brevo_api' => $this->sendViaBrevoApi($email, $code, $userName, $expiryMinutes),
            default     => Log::info("📧 Email OTP for [{$email}]: {$code}"),
        };
    }

    /**
     * Send email OTP via Brevo HTTP API (bypasses SMTP port blocks)
     */
    protected function sendViaBrevoApi(string $email, string $code, string $userName, int $expiryMinutes): void
    {
        $apiKey = config('services.brevo.api_key');
        $fromEmail = config('mail.from.address', 'noreply@sal7ly.com');
        $fromName  = config('mail.from.name', 'Sal7ly');

        // Render the Blade email template to HTML
        $htmlContent = view('emails.otp', [
            'code'          => $code,
            'userName'      => $userName,
            'expiryMinutes' => $expiryMinutes,
        ])->render();

        $response = Http::withHeaders([
            'api-key'      => $apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender'      => ['name' => $fromName, 'email' => $fromEmail],
            'to'          => [['email' => $email, 'name' => $userName]],
            'subject'     => 'Your Sal7ly Verification Code',
            'htmlContent' => $htmlContent,
        ]);

        if ($response->failed()) {
            Log::error('Brevo API email failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Failed to send OTP email via Brevo API: ' . $response->body());
        }

        Log::info("📧 Email OTP sent via Brevo API to [{$email}]");
    }

    /**
     * Send OTP via Ultramsg (WhatsApp Web API wrapper)
     */
    protected function sendViaUltramsg(string $phone, string $code): void
    {
        $instanceId = config('services.ultramsg.instance_id');
        $token      = config('services.ultramsg.token');

        if (!$instanceId || !$token) {
            Log::error('Ultramsg credentials not configured. Check ULTRAMSG_INSTANCE_ID and ULTRAMSG_TOKEN in .env');
            throw new \RuntimeException('Ultramsg credentials are not configured.');
        }

        // Format phone number to international format for Ultramsg
        $formattedPhone = $phone;
        
        // If it's a local Egyptian number starting with 01 (e.g. 01018900258), convert to +20...
        if (preg_match('/^0(1[0125][0-9]{8})$/', $phone, $matches)) {
            $formattedPhone = '+20' . $matches[1];
        } 
        // If it starts with 20 and is an Egyptian number but missing the '+', add it
        elseif (preg_match('/^201[0125][0-9]{8}$/', $phone)) {
            $formattedPhone = '+' . $phone;
        } 
        // Otherwise, just ensure it has a '+'
        elseif (!str_starts_with($phone, '+')) {
            $formattedPhone = '+' . ltrim($phone, '0');
        }

        $response = Http::asForm()->post("https://api.ultramsg.com/{$instanceId}/messages/chat", [
            'token'    => $token,
            'to'       => $formattedPhone,
            'body'     => "Your Sal7ly verification code is: *{$code}*. Valid for " . config('services.otp.expiry_minutes', 10) . " minutes.",
            'priority' => 10,
        ]);

        if ($response->failed()) {
            Log::error('Ultramsg WhatsApp failed', [
                'phone'  => $phone,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Failed to send OTP via Ultramsg: ' . $response->body());
        }

        Log::info("💬 OTP sent via Ultramsg to [{$phone}]");
    }
}