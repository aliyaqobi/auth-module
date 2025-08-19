<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Modules\Auth\Http\Requests\SendCodeNewEmailChangeRequest;
use Modules\Auth\Http\Requests\VerifyCodeNewEmailChangeRequest;
use Modules\Auth\Http\Requests\VerifyCurrentEmailChangeRequest;
use Modules\Auth\Service\AuthService;
use Modules\Auth\Service\UserService;
use Modules\Auth\Service\VerificationService;

class EmailChangeController extends ApiController
{
    public function __construct(
        public AuthService         $authService,
        public UserService         $userService,
        public VerificationService $verificationService
    ) {}

    public function send_code_current_email(Request $request): JsonResponse
    {
        $this->verificationService->sendEmailVerification(
            email: $request->user()->email,
            ip: $request->ip()
        );

        return $this->success();
    }

    public function verify_current_email(VerifyCurrentEmailChangeRequest $request): JsonResponse
    {
        $this->verificationService->forgetCode($request->ip(), $request->user()->email, 'email');

        return $this->success([
            'reset_token' => Password::createToken($request->user()),
        ]);
    }

    public function send_code_new_email(SendCodeNewEmailChangeRequest $request)
    {
        $this->verificationService->sendEmailVerification(
            email: $request->get('email'),
            ip: $request->ip()
        );
        return $this->success();
    }

    public function verify_new_email(VerifyCodeNewEmailChangeRequest $request)
    {
        $this->verificationService->forgetCode($request->ip(), $request->email, 'email');
        $this->userService->update([
            'email' => $request->email,
            'country_code' => $request->country_code,
        ], $request->user());

        return $this->success();
    }
}
