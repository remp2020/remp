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

    /**
     * Returns true when $ip is within this range. Only IPv4 is supported;
     * IPv6 (or any value rejected by ip2long()) returns false.
     */
    public function containsIp(string $ip): bool
    {
        $ipLong = ip2long($ip);
        $fromLong = ip2long($this->ip_from);

        if ($ipLong === false || $fromLong === false) {
            return false;
        }

        if ($this->ip_to === null) {
            return $ipLong === $fromLong;
        }

        $toLong = ip2long($this->ip_to);
        if ($toLong === false) {
            return false;
        }

        return $ipLong >= $fromLong && $ipLong <= $toLong;
    }
}
