<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginCodeRequest extends FormRequest
{
    public function rules()
    {
        return [
            // 🎯 یا ایمیل یا phone باید باشه و در دیتابیس موجود باشه
            'email'        => ['required_without:phone', 'email', 'exists:users,email'],
            'phone'        => ['required_without:email', 'string', 'regex:/^[0-9]{10,15}$/', 'exists:users,phone'],
            'country_code' => ['required_if:phone,present', 'string', 'regex:/^[0-9]{1,5}$/'],
        ];
    }

    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [
            'email.required_without' => 'Email is required when phone is not provided.',
            'phone.required_without' => 'Phone is required when email is not provided.',
            'phone.regex' => 'Phone number must contain only digits and be 10-15 characters long.',
            'country_code.regex' => 'Country code must contain only digits and be 1-5 characters long.',
            'email.exists' => 'This email is not registered.',
            'phone.exists' => 'This phone number is not registered.',
        ];
    }
}
