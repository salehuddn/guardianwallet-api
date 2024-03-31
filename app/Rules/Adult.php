<?php

namespace App\Rules;

use Closure;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;

class Adult implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $dob = Carbon::parse($value);
        // $now = Carbon::now();
        // $age = $dob->diffInYears($now);

        $isAdult = Carbon::parse($value)->addYears(19)->lte(Carbon::now());

        if ($isAdult === false) {
            $fail("The age must be 19 years and above.");
        }
    }
}
