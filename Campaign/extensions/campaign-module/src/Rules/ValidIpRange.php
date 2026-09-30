<?php

namespace Remp\CampaignModule\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Remp\CampaignModule\CampaignIpRange;

class ValidIpRange implements ValidationRule
{
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ipFrom = data_get($value, 'ip_from');
        $ipTo = data_get($value, 'ip_to');

        if ($ipFrom === null || (is_string($ipFrom) && trim($ipFrom) === '')) {
            $fail('IP "from" address is required.');
            return;
        }

        $from = $this->pack('from', $ipFrom, $fail);

        if ($ipTo === null || $ipTo === '') {
            return;
        }

        $to = $this->pack('to', $ipTo, $fail);

        if ($from === null || $to === null) {
            return;
        }

        if (strlen($from) !== strlen($to)) {
            $fail("IP \"from\" address \"{$ipFrom}\" and IP \"to\" address \"{$ipTo}\" must be both IPv4 or both IPv6.");
            return;
        }

        if (strcmp($from, $to) > 0) {
            $fail("IP \"from\" address \"{$ipFrom}\" must be lower than or equal to IP \"to\" address \"{$ipTo}\".");
        }
    }

    // filter_var first: same acceptance as Laravel's `ip` rule. inet_pton alone is platform-dependent
    // (macOS accepts "01.2.3.4" and "fe80::1%eth0", Linux does not) and throws ValueError on a NUL byte.
    private function pack(string $bound, mixed $ip, Closure $fail): ?string
    {
        $packed = is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false
            ? CampaignIpRange::packIp($ip)
            : null;

        if ($packed === null) {
            $fail(is_string($ip)
                ? "IP \"{$bound}\" address \"{$ip}\" is not a valid IP address."
                : "IP \"{$bound}\" address is not a valid IP address.");
        }

        return $packed;
    }
}
