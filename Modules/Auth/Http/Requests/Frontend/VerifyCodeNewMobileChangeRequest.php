<?php

namespace Modules\Auth\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\ResetToken;
use Modules\Auth\Rules\VerificationCode;

class VerifyCodeNewMobileChangeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'code'         => [
                'required',
                'numeric',
                'digits:' . config('auth.verification_length', 5),
                new VerificationCode('mobile', request('mobile')),
            ],
            'country_code' => ['required', 'numeric'],
            'mobile'       => ['required', 'string', 'unique:users'],
            'reset_token'  => ['required', new ResetToken()],
        ];
    }

    public function authorize()
    {
        return true;
    }
}
