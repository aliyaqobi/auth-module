<?php

namespace Modules\Auth\Http\Controllers\Frontend;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\Frontend\RegisterCodeRequest;
use Modules\Auth\Http\Requests\Frontend\RegisterRequest;
use Modules\Auth\Service\AuthService;
use Modules\Auth\Service\VerificationService;
use Modules\Auth\Service\UsernameService;
use Modules\Auth\Transformers\Frontend\UserResource;

class RegisterController extends ApiController
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

    public function register(RegisterRequest $request): JsonResponse
    {
        // 🎯 حذف کد از cache
        if ($request->email) {
            $this->verificationService->forgetCode($request->ip(), $request->email, 'email');
        }
        if ($request->phone) {
            $this->verificationService->forgetCode($request->ip(), $request->phone, 'mobile');
        }

        // 🎯 آماده سازی داده های کاربر
        $userData = [
            'name' => $request->name,
            'registration_type' => 'normal',
        ];

        // 🎯 تعیین primary identifier
        if ($request->email) {
            $userData['email'] = $request->email;
        }
        if ($request->phone) {
            $userData['phone'] = $request->phone;
            $userData['country_code'] = $request->country_code;
        }

        // 🎯 تولید username
        $userData['username'] = UsernameService::generateFromName($request->name);

        // 🎯 فیلدهای اختیاری
        if ($request->filled('province_id')) {
            $userData['province_id'] = $request->province_id;
        }

        if ($request->filled('city_id')) {
            $userData['city_id'] = $request->city_id;
        }

        $user = $this->authService->createUser($userData);

        $tokenResult = $this->authService->createAccessToken($user, $request->device_name, true);

        return $this->success([
            'user' => new UserResource($user),
            'access_token' => $tokenResult,
            'token_type' => 'Bearer',
        ], __('Welcome to :app_name', ['app_name' => config('app.name')]));
    }

    public function code(RegisterCodeRequest $request): JsonResponse
    {
        // 🎯 ارسال کد به ایمیل یا موبایل
        if ($request->email) {
            $this->verificationService->sendEmailVerification(
                email: $request->email,
                ip: $request->ip(),
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
