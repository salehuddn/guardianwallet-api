<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Children implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isChildren = Carbon::parse($value)->addYears(19)->lte(Carbon::now());

        if ($isChildren === true) {
            $fail("The age must be 18 years and below.");
        }
    }
}
