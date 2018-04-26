<?php

namespace App;

use DB;
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

    protected $fillable = [
        'name',
        'signed_in',
        'once_per_session',
        'pageview_rules',
        'devices'
    ];

    protected $casts = [
        'active' => 'boolean',
        'signed_in' => 'boolean',
        'once_per_session' => 'boolean',
        'pageview_rules' => 'json',
        'devices' => 'json'
    ];

    protected $attributes = [
        'once_per_session' => false,
        'pageview_rules' => '[]',
        'devices' => "[\"desktop\", \"mobile\"]"
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

    public function banners()
    {
        return $this->belongsToMany(Banner::class, 'campaign_banners')
            ->withPivot('variant', 'proportion', 'control_group');
    }

    public function banner()
    {
        return $this->banners()->orderBy('weight', 'ASC')->first()->get();
    }

    public function getBannerAttribute()
    {
        if ($this->relationLoaded('banner')) {
            return $this->getRelation('banner')->first();
        }
        return $this->banner()->first();
    }

    public function setBannerIdAttribute($value)
    {
        $data = [$value => ['variant' => 'A']];

        $this->banner()->sync($data);
    }

    public function getAllVariants()
    {
        return DB::table('campaign_banners')
            ->where('campaign_id', $this->id)
            ->orderBy('weight')->get();
    }

    public function removeVariants(array $variantIds)
    {
        return DB::table('campaign_banners')
            ->where('id', 'in', $variantIds)
            ->update(['deleted_at' => now()]);
    }

    public function setVariantsAttribute(array $variants)
    {
        $data = [];

        foreach($variants as $variant) {
            $data = [
                'id' => $variant['id'],
                'campaign_id' => $this->id,
                'variant' => $variant['name'],
                'weight' => $variant['weight'],
                'proportion' => $variant['proportion'],
                'control_group' => $variant['control_group'],
                'banner_id' => $variant['banner_id'] ?? null,
            ];

            if (isset($variant['id'])) {
                DB::table('campaign_banners')->where('id', $data['id'])->update($data);
            } else {
                DB::table('campaign_banners')->insert($data);
            }
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

    public function cache()
    {
        $activeCampaignIds = Schedule::applyScopes()->runningOrPlanned()->orderBy('start_time')->pluck('campaign_id')->unique()->toArray();
        $campaign = $this->where(['id' => $this->id])->with([
            'segments',
            'banner',
            'banner.htmlTemplate',
            'banner.mediumRectangleTemplate',
            'banner.barTemplate',
            'banner.shortMessageTemplate',
            'countries',
            'countriesWhitelist',
            'countriesBlacklist',
            'schedules',
        ])->first();
        Cache::forever(self::ACTIVE_CAMPAIGN_IDS, $activeCampaignIds);
        Cache::tags([self::CAMPAIGN_TAG])->forever($this->id, $campaign);
    }
}
