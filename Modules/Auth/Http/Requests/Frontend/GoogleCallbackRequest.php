<?php

namespace Modules\Auth\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class GoogleCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'min:10',
                'max:500',
                'regex:/^[a-zA-Z0-9\/_\-\.=]+$/'
            ],
            'device_name' => [
                'nullable',
                'string',
                'min:1',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-_]+$/'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Authorization code is required.',
            'code.regex' => 'Authorization code contains invalid characters.',
            'device_name.regex' => 'Device name contains invalid characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('device_name')) {
            $this->merge([
                'device_name' => $this->sanitizeDeviceName($this->input('device_name'))
            ]);
        } else {
            $this->merge([
                'device_name' => $this->getDefaultDeviceName()
            ]);
        }
    }

    private function sanitizeDeviceName(?string $deviceName): string
    {
        if (empty($deviceName)) {
            return $this->getDefaultDeviceName();
        }

        $deviceName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', trim($deviceName));

        if (empty($deviceName)) {
            return $this->getDefaultDeviceName();
        }

        return substr($deviceName, 0, 100);
    }

    private function getDefaultDeviceName(): string
    {
        $userAgent = $this->userAgent() ?? '';

        if (str_contains($userAgent, 'Chrome')) {
            return 'google-oauth-chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            return 'google-oauth-firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'google-oauth-safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            return 'google-oauth-edge';
        }

        return 'google-oauth-web';
    }
}
