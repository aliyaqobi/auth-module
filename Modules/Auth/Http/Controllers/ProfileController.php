<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Http\Requests\ProfileUpdateRequest;
use Modules\Auth\Service\UserService;
use Modules\Auth\Transformers\UserResource;

class ProfileController extends ApiController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return $this->success(new UserResource($user));

        } catch (\Exception $e) {
            Log::error('User show error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error('Failed to load user profile', [], 500);
        }
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        try {
            $this->userService->update($request->validated(), $request->user());

            $user = $request->user()->fresh();

            return $this->success(new UserResource($user), 'User information has been updated.');

        } catch (\Exception $e) {
            Log::error('User update error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error('Failed to update user profile', [], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()
                ->currentAccessToken()
                ->delete();

            return $this->success(null, "User logged out successfully.");

        } catch (\Exception $e) {
            Log::error('User logout error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error('Failed to logout', [], 500);
        }
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            $this->userService->uploadProfilePhoto($request->user(), $request->file('file'));

            $user = $request->user()->fresh();

            return $this->success(new UserResource($user));

        } catch (\Exception $e) {
            Log::error('Avatar upload error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error('Failed to upload avatar', [], 500);
        }
    }
}
