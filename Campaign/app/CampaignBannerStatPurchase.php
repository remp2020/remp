<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignBannerStatPurchase extends Model
{
    public $timestamps = false;

    protected $table = 'campaign_banner_stat_purchases';

    protected $fillable = [
        'campaign_banner_stat_id',
        'sum',
        'currency',
    ];

    public function banner()
    {
        return $this->belongsTo(CampaignBannerStats::class);
    }
}
