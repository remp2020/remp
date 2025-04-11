<?php

namespace Remp\BeamModule\Model\Rules;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Validation\Rule;

class ValidCarbonDate implements Rule
{
    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        try {
            Carbon::parse($value);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute is not a valid date.';
    }
}
