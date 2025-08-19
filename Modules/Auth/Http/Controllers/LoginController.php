<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\LoginCodeRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Service\AuthService;
use Modules\Auth\Service\VerificationService;
use Modules\Auth\Transformers\UserResource;

class LoginController extends ApiController
{
    private AuthService $authService;
    private VerificationService $verificationService;

    public function __construct(
        AuthService $authService,
        VerificationService $verificationService
    ) {
        $this->authService = $authService;
        $this->verificationService = $verificationService;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // 🎯 حذف کد از cache
        if ($request->email) {
            $this->verificationService->forgetCode($request->ip(), $request->email, 'email');
        }
        if ($request->phone) {
            $this->verificationService->forgetCode($request->ip(), $request->phone, 'mobile');
        }

        // دریافت کاربر از request
        $user = $request->getAuthUser();

        if (!$user) {
            return $this->error('User not found', [], 404);
        }

        $tokenResult = $this->authService->createAccessToken($user, $request->device_name);

        return $this->success([
            'user' => new UserResource($user),
            'access_token' => $tokenResult,
            'token_type' => 'Bearer',
        ], __('Welcome to :app_name', ['app_name' => config('app.name')]));
    }

    public function code(LoginCodeRequest $request): JsonResponse
    {
        // 🎯 ارسال کد به ایمیل یا موبایل
        if ($request->email) {
            $this->verificationService->sendEmailVerification(
                email: $request->email,
                ip: $request->ip()
            );
        }

        if ($request->phone) {
            $this->verificationService->sendSmsVerification(
                mobile: $request->phone, // Note: service still expects 'mobile' parameter name
                ip: $request->ip()
            );
        }

        return $this->success();
    }
}
