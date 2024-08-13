<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Model;

class CampaignBannerPurchaseStats extends Model
{
    public $timestamps = false;

    protected $table = 'campaign_banner_purchase_stats';

    protected $fillable = [
        'campaign_banner_id',
        'time_from',
        'time_to',
        'sum',
        'currency',
    ];

    protected $dates = [
        'time_from',
        'time_to',
    ];

    public function campaignBanner()
    {
        return $this->belongsTo(CampaignBanner::class);
    }
}
