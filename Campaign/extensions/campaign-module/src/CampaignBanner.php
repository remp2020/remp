<?php

namespace Remp\CampaignModule;

use Database\Factories\CampaignBannerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Remp\CampaignModule\Concerns\HasCacheableRelation;

class CampaignBanner extends Model
{
    use HasFactory;
    use HasCacheableRelation;

    use SoftDeletes;
    use IdentificationTrait;

    protected $table = 'campaign_banners';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'campaign_id',
        'banner_id',
        'control_group',
        'proportion',
        'weight',
    ];

    protected $cacheableRelations = [
        'banner' => Banner::class,
    ];

    protected static function newFactory(): CampaignBannerFactory
    {
        return CampaignBannerFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (CampaignBanner $variant) {
            $variant->uuid = self::generateUuid();
            $variant->public_id = self::generatePublicId();
        });
    }

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * This method overrides Laravel's default replicate method
     * it's removing unique & constraint attributes (uuid, campaign_id)
     *
     * @param array|null $except
     * @return CampaignBanner
     */
    public function replicate(array $except = null)
    {
        $replica = parent::replicate($except);

        unset($replica['uuid']);
        unset($replica['campaign_id']);

        return $replica;
    }
}
