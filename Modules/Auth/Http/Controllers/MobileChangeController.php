<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Modules\Auth\Http\Requests\SendCodeNewMobileChangeRequest;
use Modules\Auth\Http\Requests\VerifyCodeNewMobileChangeRequest;
use Modules\Auth\Http\Requests\VerifyCurrentMobileChangeRequest;
use Modules\Auth\Service\AuthService;
use Modules\Auth\Service\UserService;
use Modules\Auth\Service\VerificationService;

class MobileChangeController extends ApiController
{
    public function __construct(
        public AuthService         $authService,
        public UserService         $userService,
        public VerificationService $verificationService
    ) {}

    public function send_code_current_mobile(Request $request): JsonResponse
    {
        $this->verificationService->sendSmsVerification(
            mobile: $request->user()->mobile,
            ip: $request->ip()
        );

        return $this->success();
    }

    public function verify_current_mobile(VerifyCurrentMobileChangeRequest $request): JsonResponse
    {
        $this->verificationService->forgetCode($request->ip(), $request->user()->mobile, 'mobile');

        return $this->success([
            'reset_token' => Password::createToken($request->user()),
        ]);
    }

    public function send_code_new_mobile(SendCodeNewMobileChangeRequest $request)
    {
        $this->verificationService->sendSmsVerification(
            mobile: $request->get('mobile'),
            ip: $request->ip()
        );
        return $this->success();
    }

    public function verify_new_mobile(VerifyCodeNewMobileChangeRequest $request)
    {
        $this->verificationService->forgetCode($request->ip(), $request->mobile, 'mobile');
        $this->userService->update([
            'mobile' => $request->mobile,
            'country_code' => $request->country_code,
        ], $request->user());

        return $this->success();
    }
}
