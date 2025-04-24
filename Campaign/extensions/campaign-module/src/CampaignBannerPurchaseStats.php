<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<CampaignBanner, $this>
     */
    public function campaignBanner(): BelongsTo
    {
        return $this->belongsTo(CampaignBanner::class);
    }
}
