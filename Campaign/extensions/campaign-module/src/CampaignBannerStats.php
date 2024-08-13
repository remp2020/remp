<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Model;

class CampaignBannerStats extends Model
{
    public $timestamps = false;

    protected $table = 'campaign_banner_stats';

    protected $fillable = [
        'campaign_banner_id',
        'time_from',
        'time_to',
        'click_count',
        'show_count',
        'payment_count',
        'purchase_count',
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
