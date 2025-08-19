<?php

namespace Modules\Auth\Service;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Entities\User;

class UserService
{
    public function findByEmail(string $email): ?User
    {
        return User::byEmail($email)->first();
    }

    public function update(array $data, User $user): bool
    {
        return $user->update($data);
    }

    public function uploadProfilePhoto(User $user, UploadedFile $photo): void
    {
        // Create uploads directory if it doesn't exist
        $uploadPath = storage_path('app/public/uploads/avatars');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Store the file
        $filename = $user->id . '_' . time() . '.' . $photo->getClientOriginalExtension();
        $photo->storeAs('public/uploads/avatars', $filename);
        
        // Update user's profile photo path
        $user->update([
            'profile_photo_path' => 'uploads/avatars/' . $filename
        ]);

        Log::info('Profile photo uploaded', [
            'user_id' => $user->id,
            'filename' => $filename
        ]);
    }
}
