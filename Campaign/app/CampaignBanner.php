<?php

namespace App;

use App\Banner;
use App\Campaign;
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

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
