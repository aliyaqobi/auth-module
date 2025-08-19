<?php

namespace Modules\Auth\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\ResetToken;

class SendCodeNewEmailChangeRequest extends FormRequest
{
    public function rules()
    {
        return [
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
