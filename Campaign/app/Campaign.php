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
        'alt_banner_id',
        'signed_in',
        'active',
        'once_per_session',
    ];

    protected $casts = [
        'active' => 'boolean',
        'signed_in' => 'boolean',
        'once_per_session' => 'boolean',
    ];

    protected $attributes = [
        'active' => false,
        'once_per_session' => false,
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

    public function altBanner()
    {
        return $this->belongsTo(Banner::class, 'alt_banner_id');
    }

    public function segments()
    {
        return $this->hasMany(CampaignSegment::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function cache()
    {
        $activeCampaignIds = self::whereActive(true)->pluck('id')->toArray();
        $campaign = $this->where(['id' => $this->id])->with([
            'segments',
            'banner',
            'banner.htmlTemplate',
            'banner.mediumRectangleTemplate',
            'banner.barTemplate',
            'altBanner',
            'altBanner.htmlTemplate',
            'altBanner.mediumRectangleTemplate',
            'altBanner.barTemplate',
            'schedules',
        ])->first();
        Cache::forever(self::ACTIVE_CAMPAIGN_IDS, $activeCampaignIds);
        Cache::tags([self::CAMPAIGN_TAG])->forever($this->id, $campaign);
    }
}
