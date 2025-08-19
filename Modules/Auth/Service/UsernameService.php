<?php

namespace Modules\Auth\Service;

use Illuminate\Support\Str;
use Modules\Auth\Entities\User;

class UsernameService
{
    /**
     * Generate unique username from name + timestamp
     */
    public static function generateFromName(string $name): string
    {
        // Clean name
        $cleanName = self::cleanName($name);

        // Get current minute and second
        $timestamp = now()->format('is'); // i=minute, s=second

        // Combine name + time
        $username = $cleanName . $timestamp;

        // Check uniqueness
        return self::ensureUnique($username);
    }

    /**
     * Clean name for username
     */
    private static function cleanName(string $name): string
    {
        // Convert to lowercase
        $clean = Str::lower($name);

        // Remove invalid characters and replace with _
        $clean = preg_replace('/[^a-z0-9_]/', '_', $clean);

        // Remove consecutive underscores
        $clean = preg_replace('/_+/', '_', $clean);

        // Remove underscores from start and end
        $clean = trim($clean, '_');

        // If empty, use default
        return $clean ?: 'user';
    }

    /**
     * Ensure username is unique
     */
    private static function ensureUnique(string $username): string
    {
        $original = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $original . '_' . $counter;
            $counter++;

            // Prevent infinite loop
            if ($counter > 999) {
                $username = $original . '_' . Str::random(3);
                break;
            }
        }

        return $username;
    }
}
