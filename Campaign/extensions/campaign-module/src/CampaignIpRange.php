<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignIpRange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'ip_from',
        'ip_to',
        'blacklisted',
    ];

    protected $casts = [
        'blacklisted' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public static function packIp(string $ip): ?string
    {
        return inet_pton($ip) ?: null;
    }

    public function isBlacklisted(): bool
    {
        return (bool) ($this->attributes['blacklisted'] ?? false);
    }

    public function containsIp(string $ip): bool
    {
        $packedIp = self::packIp($ip);

        return $packedIp !== null && $this->containsPackedIp($packedIp);
    }

    public function containsPackedIp(string $packedIp): bool
    {
        $from = self::packIp($this->attributes['ip_from']);
        $to = isset($this->attributes['ip_to']) ? self::packIp($this->attributes['ip_to']) : $from;
        $len = strlen($packedIp);

        // IPv4 packs to 4 bytes, IPv6 to 16: families never match
        // strcmp, not <=: PHP compares numeric-looking binary strings numerically
        return $from !== null && $to !== null
            && strlen($from) === $len && strlen($to) === $len
            && strcmp($packedIp, $from) >= 0 && strcmp($packedIp, $to) <= 0;
    }
}
