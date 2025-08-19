<?php

namespace Modules\Auth\Service;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Entities\User;

class GoogleOAuthService
{
    /**
     * Handle Google OAuth callback
     */
    public function handleCallback(string $code): array
    {
        DB::beginTransaction();

        try {
            $googleUser = $this->getGoogleUserData($code);
            $this->validateGoogleUserData($googleUser);
            $user = $this->findOrCreateUser($googleUser);

            DB::commit();

            Log::info('Google OAuth authentication successful', [
                'user_id' => $user->id,
                'google_id' => $googleUser->id
            ]);

            return ['user' => $user];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Google OAuth authentication failed', [
                'error' => $e->getMessage(),
                'code' => $code ? 'present' : 'missing'
            ]);
            throw new Exception('Authentication failed. Please try again.');
        }
    }

    /**
     * Get Google OAuth redirect URL
     */
    public function getRedirectUrl(): string
    {
        try {
            return Socialite::driver('google')
                ->stateless()
                ->scopes(['openid', 'profile', 'email'])
                ->with([
                    'access_type' => 'offline',
                    'prompt' => 'consent select_account'
                ])
                ->redirect()
                ->getTargetUrl();
        } catch (Exception $e) {
            Log::error('Google OAuth redirect URL generation failed', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Unable to generate Google OAuth URL');
        }
    }

    /**
     * Get user data from Google
     */
    private function getGoogleUserData(string $code)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser || !$googleUser->id) {
                throw new Exception('Failed to retrieve user data from Google');
            }

            return $googleUser;

        } catch (Exception $e) {
            Log::error('Failed to get Google user data', [
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to authenticate with Google');
        }
    }

    /**
     * Validate Google user data
     */
    private function validateGoogleUserData($googleUser): void
    {
        if (empty($googleUser->id)) {
            throw new Exception('Google ID is missing');
        }

        if (empty($googleUser->email) || !filter_var($googleUser->email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email is required from Google');
        }

        if (empty($googleUser->name)) {
            throw new Exception('Name is required from Google');
        }
    }

    /**
     * Find or create user based on Google data
     */
    private function findOrCreateUser($googleUser): User
    {
        // First, try to find by Google ID
        $user = User::where('google_id', $googleUser->id)->first();
        if ($user) {
            $this->updateExistingGoogleUser($user, $googleUser);
            return $user;
        }

        // Then try to find by email
        $user = User::where('email', $googleUser->email)->first();
        if ($user) {
            $this->linkGoogleAccountToExistingUser($user, $googleUser);
            return $user;
        }

        // Create new user
        return $this->createNewUserFromGoogle($googleUser);
    }

    /**
     * Update existing Google user
     */
    private function updateExistingGoogleUser(User $user, $googleUser): void
    {
        $updateData = [
            'name' => $googleUser->name,
            'google_token_expires_at' => now()->addDays(30),
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'is_active' => 1
        ];

        if (!empty($googleUser->avatar) && $user->avatar !== $googleUser->avatar) {
            $updateData['avatar'] = $googleUser->avatar;
        }

        $user->update($updateData);
    }

    /**
     * Link Google account to existing user
     */
    private function linkGoogleAccountToExistingUser(User $user, $googleUser): void
    {
        // Security check
        $existingGoogleUser = User::where('google_id', $googleUser->id)
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingGoogleUser) {
            throw new Exception('This Google account is already linked to another user');
        }

        // Save Google token in password
        $googleToken = $googleUser->token ?? Str::random(60);

        $updateData = [
            'google_id' => $googleUser->id,
            'password' => Hash::make($googleToken),
            'google_token_expires_at' => now()->addDays(30),
            'email_verified_at' => now(),
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'is_active' => 1
        ];

        if ((empty($user->avatar) || $user->avatar !== $googleUser->avatar) && !empty($googleUser->avatar)) {
            $updateData['avatar'] = $googleUser->avatar;
        }

        $user->update($updateData);
    }

    /**
     * Create new user from Google data
     */
    private function createNewUserFromGoogle($googleUser): User
    {
        // Save Google token in password
        $googleToken = $googleUser->token ?? Str::random(60);

        $userData = [
            'name' => $this->sanitizeName($googleUser->name),
            'email' => strtolower(trim($googleUser->email)),
            'username' => $this->generateUsernameFromEmail($googleUser->email),
            'google_id' => $googleUser->id,
            'password' => Hash::make($googleToken),
            'avatar' => !empty($googleUser->avatar) ? $googleUser->avatar : null,
            'mobile' => null,
            'email_verified_at' => now(),
            'google_token_expires_at' => now()->addDays(30),
            'registration_type' => User::REGISTRATION_GOOGLE,
            'is_active' => 1,
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ];

        return User::create($userData);
    }

    /**
     * Generate username from Google name + timestamp
     */
    private function generateUsernameFromEmail(string $email): string
    {
        $emailUsername = explode('@', $email)[0];
        $cleanUsername = UsernameService::generateFromName($emailUsername);

        return $cleanUsername;
    }

    /**
     * Sanitize user name
     */
    private function sanitizeName(string $name): string
    {
        $name = strip_tags(trim($name));

        if (strlen($name) > 150) {
            $name = substr($name, 0, 150);
        }

        return $name ?: 'User';
    }

    /**
     * Revoke Google access and unlink account
     */
    public function revokeAccess(User $user): bool
    {
        if (!$user->google_id) {
            return false;
        }

        DB::beginTransaction();

        try {
            // Check if user has other login methods
            if (empty($user->password) && empty($user->mobile)) {
                throw new Exception('Cannot unlink Google account. Please set a password or mobile number first.');
            }

            $user->update([
                'google_id' => null,
                'google_token_expires_at' => null,
                'avatar' => null,
            ]);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to unlink Google account', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
