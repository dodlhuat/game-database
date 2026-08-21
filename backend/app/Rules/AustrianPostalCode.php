<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Austrian postal codes are exactly 4 digits (e.g. "1010" for Vienna's 1st
 * district). This app only serves Austrian addresses today — see the
 * membership upgrade / account address fields.
 */
class AustrianPostalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\d{4}$/', $value)) {
            $fail('Die Postleitzahl muss aus genau 4 Ziffern bestehen.');
        }
    }
}
