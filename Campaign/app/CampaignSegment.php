<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\CampaignSegment
 *
 * @property int $id
 * @property int $campaign_id
 * @property string $code
 * @property string $provider
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Campaign $campaign
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignSegment whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignSegment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignSegment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignSegment whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignSegment whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CampaignSegment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CampaignSegment extends Model
{
    protected $appends = ['name', 'group'];

    public $name;

    public $group;

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getNameAttribute()
    {
        return $this->name;
    }

    public function getGroupAttribute()
    {
        return $this->group;
    }
}
