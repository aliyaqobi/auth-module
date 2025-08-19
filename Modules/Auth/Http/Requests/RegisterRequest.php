<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\VerificationCode;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            // 🎯 یا ایمیل یا phone باید باشه
            'email'        => ['required_without:phone', 'email', 'unique:users,email'],
            'phone'        => ['required_without:email', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:users,phone'],
            'country_code' => ['required_if:phone,present', 'string', 'regex:/^[0-9]{1,5}$/'],

            // 🎯 فیلدهای اجباری
            'name'         => ['required', 'string', 'min:2', 'max:150'],
            'device_name'  => ['required', 'string', 'min:1', 'max:100'],

            // 🎯 فیلدهای اختیاری
            'province_id'  => ['nullable', 'integer'],
            'city_id'      => ['nullable', 'integer'],
            'username'     => ['nullable', 'string', 'min:3', 'max:100', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users'],
        ];

        // 🎯 اضافه کردن validation برای کد بر اساس نوع ثبت نام
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
                new VerificationCode('phone', $this->phone), // حالا از phone استفاده می‌کنیم
            ];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'email.required_without' => 'Email is required when phone is not provided.',
            'phone.required_without' => 'Phone is required when email is not provided.',
            'phone.regex' => 'Phone number must contain only digits and be 10-15 characters long.',
            'country_code.regex' => 'Country code must contain only digits and be 1-5 characters long.',
            'name.min' => 'Name must be at least 2 characters.',
            'name.max' => 'Name must not exceed 150 characters.',
            'email_code.required' => 'Email verification code is required.',
            'phone_code.required' => 'Phone verification code is required.',
            'country_code.required_if' => 'Country code is required when phone is provided.',
            'email.unique' => 'This email is already registered.',
            'phone.unique' => 'This phone number is already registered.',
            'username.regex' => 'Username can only contain letters, numbers and underscores.',
            'username.unique' => 'This username is already taken.',
        ];
    }
}
