<?php

namespace Modules\Auth\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Password;

class ResetToken implements Rule
{
    public function passes($attribute, $value)
    {
        return Password::tokenExists(request()->user(), $value);
    }

    public function message()
    {
        return 'The reset token is invalid.';
    }
}
