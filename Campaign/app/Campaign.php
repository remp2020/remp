<?php

namespace App;

use Cache;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Campaign extends Model
{
    const ACTIVE_CAMPAIGN_IDS = 'active_campaign_ids';
    const CAMPAIGN_TAG = 'campaign';

    protected $fillable = [
        'name',
        'banner_id',
        'signed_in',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'signed_in' => 'boolean',
    ];

    protected $attributes = [
        'active' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Campaign $campaign) {
            $campaign->uuid = Uuid::uuid4()->toString();
        });
    }

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }

    public function segments()
    {
        return $this->hasMany(CampaignSegment::class);
    }

    public function cache()
    {
        $activeCampaignIds = self::whereActive(true)->pluck('id')->toArray();
        $this->load(['segments', 'banner']);
        Cache::forever(self::ACTIVE_CAMPAIGN_IDS, $activeCampaignIds);
        Cache::tags([self::CAMPAIGN_TAG])->forever($this->id, $this);
    }
}
