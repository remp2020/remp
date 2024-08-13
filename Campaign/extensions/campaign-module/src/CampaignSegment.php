<?php

namespace Remp\CampaignModule;

use Database\Factories\CampaignSegmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignSegment extends Model
{
    use HasFactory;

    protected $appends = ['name', 'group'];

    protected $fillable = [
        'id',
        'code',
        'provider',
        'inclusive',
        'campaign_id'
    ];

    public $name;

    public $group;

    protected static function newFactory(): CampaignSegmentFactory
    {
        return CampaignSegmentFactory::new();
    }

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

    public function getOverrides()
    {
        return [
            'rtm_campaign' => $this->campaign->uuid,
        ];
    }
}
