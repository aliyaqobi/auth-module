<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\ResetToken;

class SendCodeNewMobileChangeRequest extends FormRequest
{
    public function rules()
    {
        return [
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
