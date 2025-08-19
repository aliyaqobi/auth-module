<?php

namespace Modules\Auth\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function rules()
    {
        $userId = auth()->id();

        return [
            'name'        => ['required', 'string', 'min:2', 'max:150', 'regex:/^[\pL\s\-\.\']+$/u'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id'     => ['nullable', 'integer', 'exists:cities,id'],
            'phone'       => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/', "unique:users,phone,$userId"],
            'username'    => ['nullable', 'string', 'min:3', 'max:100', 'regex:/^[a-zA-Z0-9_]+$/', "unique:users,username,$userId"],
        ];
    }

    public function authorize()
    {
        return auth()->check();
    }

    public function messages()
    {
        return [
            'name.regex' => 'Name can only contain letters, spaces, hyphens, dots and apostrophes.',
            'phone.regex' => 'Phone number must contain only digits and be 10-15 characters long.',
            'username.regex' => 'Username can only contain letters, numbers and underscores.',
        ];
    }
}
