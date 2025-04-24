<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<CampaignBanner, $this>
     */
    public function campaignBanner(): BelongsTo
    {
        return $this->belongsTo(CampaignBanner::class);
    }
}
