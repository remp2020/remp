<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\CampaignRule
 *
 * @property int $campaign_id
 * @property int $count
 * @property \Carbon\Carbon|null $created_at
 * @property string $event
 * @property int $id
 * @property int|null $timespan
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Campaign $campaign
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignRule whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignRule whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignRule whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignRule whereTimespan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignRule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CampaignRule extends Model
{
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
