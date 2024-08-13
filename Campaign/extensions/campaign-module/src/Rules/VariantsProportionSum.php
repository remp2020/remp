<?php

namespace Remp\CampaignModule\Rules;

use Illuminate\Contracts\Validation\Rule;
use Request;

class VariantsProportionSum implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $sum = 0;
        $variants = Request::get('variants');

        foreach ($variants as $variant) {
            $sum += $variant['proportion'];
        }

        if ($sum !== 100) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Sum of variant proportions must be 100.';
    }
}
