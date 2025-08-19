<?php

namespace Modules\Auth\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\VerificationCode;

class LoginRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            // 🎯 یا ایمیل یا phone باید باشه
            'email'        => ['required_without:phone', 'email', 'exists:users,email'],
            'phone'        => ['required_without:email', 'string', 'regex:/^[0-9]{10,15}$/', 'exists:users,phone'],
            'country_code' => ['required_if:phone,present', 'string', 'regex:/^[0-9]{1,5}$/'],
            'device_name'  => ['required', 'string', 'min:1', 'max:100'],
        ];

        // 🎯 اضافه کردن validation برای کد بر اساس نوع لاگین
        if ($this->has('email') && $this->email) {
            $rules['email_code'] = [
                'required',
                'numeric',
                'digits:' . config('auth.verification_length', 5),
                new VerificationCode('email', $this->email),
            ];
        }

        if ($this->has('phone') && $this->phone) {
            $rules['phone_code'] = [
                'required',
                'numeric',
                'digits:' . config('auth.verification_length', 5),
                new VerificationCode('phone', $this->phone),
            ];
        }

        return $rules;
    }

    public function authorize()
    {
        return true;
    }

    public function getAuthUser()
    {
        if ($this->email) {
            return \Modules\Auth\Entities\User::where('email', $this->email)->active()->first();
        }

        if ($this->phone) {
            return \Modules\Auth\Entities\User::where('phone', $this->phone)->active()->first();
        }

        return null;
    }

    public function messages()
    {
        return [
            'email.required_without' => 'Email is required when phone is not provided.',
            'phone.required_without' => 'Phone is required when email is not provided.',
            'phone.regex' => 'Phone number must contain only digits and be 10-15 characters long.',
            'country_code.regex' => 'Country code must contain only digits and be 1-5 characters long.',
            'email_code.required' => 'Email verification code is required.',
            'phone_code.required' => 'Phone verification code is required.',
            'email.exists' => 'This email is not registered.',
            'phone.exists' => 'This phone number is not registered.',
        ];
    }
}
