<?php

namespace App;

use Cache;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Campaign extends Model
{
    use PivotEventTrait;

    const ACTIVE_CAMPAIGN_IDS = 'active_campaign_ids';
    const CAMPAIGN_TAG = 'campaign';

    const PAGEVIEW_RULE_EVERY = 'every';
    const PAGEVIEW_RULE_SINCE = 'since';
    const PAGEVIEW_RULE_BEFORE = 'before';

    const DEVICE_MOBILE = 'mobile';
    const DEVICE_DESKTOP = 'desktop';

    const URL_FILTER_EVERYWHERE = 'everywhere';
    const URL_FILTER_ONLY_AT = 'only_at';
    const URL_FILTER_EXCEPT_AT = 'except_at';

    protected $fillable = [
        'name',
        'signed_in',
        'once_per_session',
        'pageview_rules',
        'devices',
        'using_adblock',
        'url_filter',
        'url_patterns',
    ];

    protected $casts = [
        'active' => 'boolean',
        'signed_in' => 'boolean',
        'once_per_session' => 'boolean',
        'pageview_rules' => 'json',
        'devices' => 'json',
        'using_adblock' => 'boolean',
        'url_patterns' => 'json',
    ];

    protected $attributes = [
        'once_per_session' => false,
        'using_adblock' => null,
        'pageview_rules' => '[]',
        'devices' => "[\"desktop\", \"mobile\"]",
        'url_filter' => self::URL_FILTER_EVERYWHERE,
    ];

    protected $appends = ['active'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Campaign $campaign) {
            $campaign->uuid = Uuid::uuid4()->toString();
        });
    }

    /**
     * Get virtual field `active` which indicates if campaign has active schedules.
     *
     * @return bool
     */
    public function getActiveAttribute()
    {
        if ($this->schedules()->runningOrPlanned()->count()) {
            return true;
        }
        return false;
    }

    /**
     * Get campaign variants UUIDs
     *
     * @return array
     */
    public function getVariantsUuidsAttribute()
    {
        return $this->campaignBanners()->withTrashed()->get()->map(function ($banner) {
            return $banner["uuid"];
        })->toArray();
    }

    public function banners()
    {
        return $this->belongsToMany(Banner::class, 'campaign_banners')
            ->withPivot('proportion', 'control_group', 'weight');
    }

    public function campaignBanners()
    {
        return $this->hasMany(CampaignBanner::class)->orderBy('weight');
    }

    public function getPrimaryBanner()
    {
        return optional(
            $this->campaignBanners()->with('banner')->first()
        )->banner;
    }

    public function removeVariants(array $variantIds)
    {
        return CampaignBanner::whereIn('id', $variantIds)->delete();
    }

    public function storeOrUpdateVariants(array $variants)
    {
        foreach ($variants as $variant) {
            $data = [
                'id' => $variant['id'],
                'campaign_id' => $this->id,
                'weight' => $variant['weight'],
                'proportion' => $variant['proportion'],
                'control_group' => $variant['control_group'],
                'banner_id' => $variant['banner_id'] ?? null,
            ];

            $campaignBanner = CampaignBanner::findOrNew($variant['id']);
            $campaignBanner->fill($data);
            $campaignBanner->save();
        }
    }

    public function countries()
    {
        return $this->belongsToMany(
            Country::class,
            null,
            null,
            'country_iso_code',
            null,
            'iso_code'
        )->withPivot('blacklisted');
    }

    public function countriesWhitelist()
    {
        return $this->countries()->wherePivot('blacklisted', '=', false);
    }

    public function countriesBlacklist()
    {
        return $this->countries()->wherePivot('blacklisted', '=', true);
    }

    public function segments()
    {
        return $this->hasMany(CampaignSegment::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function getAllDevices()
    {
        return [self::DEVICE_DESKTOP, self::DEVICE_MOBILE];
    }

    public function supportsDevice($device)
    {
        if (in_array($device, $this->devices)) {
            return true;
        }

        return false;
    }

    public function getVariantsProportionMapping()
    {
        $mapping = [];
        $campaignBanners = $this->campaignBanners;

        foreach ($campaignBanners as $campaignBanner) {
            $mapping[$campaignBanner->uuid] = $campaignBanner->proportion;
        }

        return $mapping;
    }

    public function getAllUrlFilterTypes()
    {
        return [
            self::URL_FILTER_EVERYWHERE => 'Everywhere',
            self::URL_FILTER_ONLY_AT => 'Only at',
            self::URL_FILTER_EXCEPT_AT => 'Except at',
        ];
    }

    /**
     * This method overrides Laravel's default replicate method
     * it loads CampaignBanner (variant) replicas to relation
     *
     * @param array|null $except
     * @return Model
     */
    public function replicate(array $except = null)
    {
        $replica = parent::replicate($except);

        $variants = [];

        foreach ($this->campaignBanners as $variant) {
            $variants[] = $variant->replicate();
        }

        $replica->setRelation('campaignBanners', collect($variants));

        return $replica;
    }

    public function cache()
    {
        $activeCampaignIds = Schedule::applyScopes()
                                ->runningOrPlanned()
                                ->orderBy('start_time')
                                ->pluck('campaign_id')
                                ->unique()
                                ->toArray();

        Cache::forever(self::ACTIVE_CAMPAIGN_IDS, $activeCampaignIds);

        $campaign = $this->where(['id' => $this->id])->with([
            'segments',
            'countries',
            'countriesWhitelist',
            'countriesBlacklist',
            'schedules',
            'campaignBanners',
            'campaignBanners.banner',
        ])->first();

        foreach ($campaign->campaignBanners as $variant) {
            optional($variant->banner)->loadTemplate();
        }

        Cache::tags([self::CAMPAIGN_TAG])->forever($this->id, $campaign);
    }

    public function signedInOptions()
    {
        return [
            null => 'Everyone',
            true => 'Only signed in',
            false => 'Only anonymous ',
        ];
    }

    public function signedInLabel()
    {
        return $this->signedInOptions()[$this->signed_in];
    }
}
