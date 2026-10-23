<?php

namespace Remp\CampaignModule\Models\Campaign;

use Remp\CampaignModule\CampaignIpRange;

readonly class CampaignIpRangeView
{
    public function __construct(
        public string $ipFrom,
        public ?string $ipTo,
        public bool $blacklisted,
    ) {
    }

    public static function fromModel(CampaignIpRange $ipRange): self
    {
        return new self(
            ipFrom: $ipRange->ip_from,
            ipTo: $ipRange->ip_to,
            blacklisted: $ipRange->isBlacklisted(),
        );
    }

    public static function fromCache(array $ipRange): self
    {
        return new self(
            ipFrom: $ipRange['ip_from'],
            ipTo: $ipRange['ip_to'],
            blacklisted: $ipRange['blacklisted'],
        );
    }

    public function toCache(): array
    {
        return [
            'ip_from' => $this->ipFrom,
            'ip_to' => $this->ipTo,
            'blacklisted' => $this->blacklisted,
        ];
    }

    public static function packIp(string $ip): ?string
    {
        return inet_pton($ip) ?: null;
    }

    public function matches(string $ip): bool
    {
        $packedIp = self::packIp($ip);
        if ($packedIp === null) {
            return false;
        }

        $from = self::packIp($this->ipFrom);
        $to = $this->ipTo !== null ? self::packIp($this->ipTo) : $from;
        $len = strlen($packedIp);

        // IPv4 packs to 4 bytes, IPv6 to 16: families never match
        // strcmp, not <=: PHP compares numeric-looking binary strings numerically
        return $from !== null && $to !== null
            && strlen($from) === $len && strlen($to) === $len
            && strcmp($packedIp, $from) >= 0 && strcmp($packedIp, $to) <= 0;
    }
}
