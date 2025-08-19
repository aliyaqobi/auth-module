<?php

namespace Modules\Auth\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Auth\Service\VerificationService;

class VerificationCode implements Rule
{
    private ?string $field_value;
    private ?string $field_name;

    public function __construct(?string $field_name = null, ?string $field_value = null)
    {
        $this->field_name = $field_name;
        $this->field_value = $field_value;
    }

    public function passes($attribute, $value)
    {
        if (is_null($this->field_value) || is_null($this->field_name)) {
            return false;
        }

        $verificationService = new VerificationService();

        // 🎯 اصلاح: اگر field_name شامل phone باشد، از mobile استفاده کن برای cache
        $cacheType = $this->field_name;
        if ($this->field_name === 'phone') {
            $cacheType = 'mobile'; // چون در VerificationService از mobile استفاده می‌شود
        }

        return $verificationService->validate(request()->ip(), $this->field_value, $value, $cacheType);
    }

    public function message()
    {
        return 'The validation code is invalid.';
    }
}
