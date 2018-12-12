<?php

namespace App;

use App\Banner;
use App\Campaign;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignBanner extends Model
{
    use SoftDeletes;

    protected $table = 'campaign_banners';

    public $timestamps = false;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'id',
        'campaign_id',
        'banner_id',
        'control_group',
        'proportion',
        'weight',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (CampaignBanner $variant) {
            $variant->uuid = Uuid::uuid4()->toString();
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
