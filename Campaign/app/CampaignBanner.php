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
        'variant',
        'weight',
        'proportion',
        'control_group',
        'banner_id'
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

    public function getClone()
    {
        return new CampaignBanner([
            "variant" => $this["variant"],
            "control_group" => $this["control_group"],
            "proportion" => $this["proportion"],
            "weight" => $this["weight"],
            "banner_id" => $this['banner_id'],
        ]);
    }
}
