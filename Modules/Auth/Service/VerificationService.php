<?php

namespace Modules\Auth\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Notifications\SendEmailVerificationNotification;
use Modules\Auth\Notifications\SendSmsVerificationNotification;

class VerificationService
{
    /**
     * Generate verification code - Dev mode with fixed code
     */
    public function generateCode(?int $length = null): int
    {
        // 🔥 DEV MODE: Fixed code for testing
        if (env('APP_ENV') === 'local' || env('DEV_MODE', false)) {
            return 12345;
        }
        
        $length = $length ?? config('auth.verification_length', 5);
        $result = null;
        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(1, 9);
        }
        return $result;
    }

    public function rememberCode(string $ip, string $username, string $code, string $type = 'email')
    {
        $this->forgetCode($ip, $username, $type);
        Cache::remember(
            key: "$ip-$type-$username",
            ttl: (60 * 15), // 15 minutes
            callback: fn(): array => [
                $type => $username,
                'code' => $code,
            ]
        );
    }

    public function sendSmsVerification(string $mobile, string $ip): int
    {
        $code = $this->generateCode();

        $this->rememberCode(
            ip: $ip,
            username: $mobile,
            code: $code,
            type: 'mobile'
        );

        // 🔥 DEV MODE: Log instead of sending actual SMS in local
        if (env('APP_ENV') === 'local' || env('DEV_MODE', false)) {
            Log::info('🔥 DEV MODE - SMS Verification Code', [
                'mobile' => $mobile,
                'code' => $code,
                'ip' => $ip,
                'message' => "SMS Code for {$mobile}: {$code}"
            ]);
            
            // Also show in console for easier debugging
            echo "\n🔥 DEV MODE - SMS Code for {$mobile}: {$code}\n";
        } else {
            // Production: Send actual SMS
            Notification::send($mobile, new SendSmsVerificationNotification(code: (string)$code));
        }

        return $code;
    }

    public function sendEmailVerification(string $email, string $ip): int
    {
        $code = $this->generateCode();

        $this->rememberCode(
            ip: $ip,
            username: $email,
            code: $code
        );

        // 🔥 DEV MODE: Log instead of sending actual email in local
        if (env('APP_ENV') === 'local' || env('DEV_MODE', false)) {
            Log::info('🔥 DEV MODE - Email Verification Code', [
                'email' => $email,
                'code' => $code,
                'ip' => $ip,
                'message' => "Email Code for {$email}: {$code}"
            ]);
            
            // Also show in console for easier debugging
            echo "\n🔥 DEV MODE - Email Code for {$email}: {$code}\n";
        } else {
            // Production: Send actual email
            Notification::route('mail', $email)->notify(new SendEmailVerificationNotification(code: (string)$code));
        }

        return $code;
    }

    public function validate(string $ip, string $username, string $code, string $type = 'email')
    {
        $cached = Cache::get(key: "$ip-$type-$username");
        
        // 🔥 DEV MODE: Extra logging for debugging
        if (env('APP_ENV') === 'local' || env('DEV_MODE', false)) {
            Log::info('🔥 DEV MODE - Validating Code', [
                'ip' => $ip,
                'username' => $username,
                'provided_code' => $code,
                'cached_code' => $cached['code'] ?? 'NOT_FOUND',
                'type' => $type,
                'cache_key' => "$ip-$type-$username"
            ]);
        }
        
        return $cached && $cached['code'] == $code;
    }

    public function forgetCode(string $ip, string $username, string $type)
    {
        Cache::forget(key: "$ip-$type-$username");
    }

    public function get(string $ip, string $username, string $type): ?int
    {
        $cached = Cache::get(key: "$ip-$type-$username");
        return $cached ? $cached['code'] : null;
    }
}
