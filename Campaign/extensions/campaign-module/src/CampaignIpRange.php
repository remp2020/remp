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

    public function isBlacklisted(): bool
    {
        return $this->blacklisted;
    }
}
