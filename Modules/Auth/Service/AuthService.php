<?php

namespace Modules\Auth\Service;

use Illuminate\Support\Facades\Log;
use Modules\Auth\Entities\User;

class AuthService
{
    public function createUser(array $data)
    {
        // 🎯 تعیین primary key برای جستجو
        $searchKey = [];

        if (isset($data['email']) && $data['email']) {
            $searchKey['email'] = $data['email'];
        } elseif (isset($data['phone']) && $data['phone']) {
            $searchKey['phone'] = $data['phone'];
        } else {
            throw new \Exception('Either email or phone is required');
        }

        // 🎯 ایجاد یا پیدا کردن کاربر
        $user = User::firstOrCreate($searchKey, $data);

        Log::info('User created/found', [
            'user_id' => $user->id,
            'search_key' => $searchKey,
            'data_provided' => array_keys($data)
        ]);

        return $user;
    }

    public function createAccessToken(User $user, $device_name, $persistent = false)
    {
        // Delete old tokens for this device
        $user->tokens()->where('name', $device_name)->delete();

        // Set expiration
        $expiresAt = $persistent ? now()->addDays(365) : now()->addDays(30);

        // Create new token
        $tokenResult = $user->createToken($device_name, ['*'], $expiresAt);

        // Update login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        Log::info('Token created', [
            'user_id' => $user->id,
            'device_name' => $device_name,
            'expires_at' => $expiresAt,
            'persistent' => $persistent
        ]);

        return $tokenResult->plainTextToken;
    }
}
