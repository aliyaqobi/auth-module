<?php

namespace Modules\Auth\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\ResetToken;
use Modules\Auth\Rules\VerificationCode;

class VerifyCodeNewEmailChangeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'code'         => [
                'required',
                'numeric',
                'digits:' . config('auth.verification_length', 5),
                new VerificationCode('email', request('email')),
            ],
            'country_code' => ['required', 'numeric'],
            'email'        => ['required', 'email', 'string', 'unique:users'],
            'reset_token'  => ['required', new ResetToken()],
        ];
    }

    public function authorize()
    {
        return true;
    }
}
