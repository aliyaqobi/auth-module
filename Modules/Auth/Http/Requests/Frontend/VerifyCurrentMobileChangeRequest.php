<?php

namespace Modules\Auth\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\VerificationCode;

class VerifyCurrentMobileChangeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'code' => [
                'required',
                'numeric',
                'digits:' . config('auth.verification_length', 5),
                new VerificationCode('mobile', auth()->user()->mobile),
            ],
        ];
    }

    public function authorize()
    {
        return true;
    }
}
