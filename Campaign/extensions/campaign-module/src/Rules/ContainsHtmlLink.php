<?php

namespace Remp\CampaignModule\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContainsHtmlLink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('The :attribute field must contain at least one HTML link.');
            return;
        }

        if (preg_match('/<\s*a[^>]*>(.*?)<\s*\/\s*a>/', $value) !== 1) {
            $fail('The :attribute field must contain at least one HTML link.');
        }
    }
}
