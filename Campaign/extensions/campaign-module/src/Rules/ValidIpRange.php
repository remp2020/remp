<?php

namespace Remp\CampaignModule\Rules;

use Illuminate\Contracts\Validation\Rule;
use Request;

class ValidIpRange implements Rule
{
    private string $failMessage = 'IP range is invalid.';

    public function passes($attribute, $value)
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!preg_match('/^ip_ranges\.(\d+)\.ip_to$/', $attribute, $matches)) {
            return true;
        }

        $ipRanges = Request::get('ip_ranges');
        $ipFrom = $ipRanges[$matches[1]]['ip_from'] ?? null;
        if ($ipFrom === null) {
            return true;
        }

        $from = ip2long($ipFrom);
        $to = ip2long($value);
        if ($from === false || $to === false) {
            return true;
        }

        if ($from > $to) {
            $this->failMessage = "IP \"from\" address \"{$ipFrom}\" must be lower than or equal to IP \"to\" address \"{$value}\".";
            return false;
        }

        return true;
    }

    public function message()
    {
        return $this->failMessage;
    }
}
