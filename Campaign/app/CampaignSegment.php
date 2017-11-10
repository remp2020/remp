<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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

    public function getOverrides()
    {
        return [
            'utm_campaign' => $this->uuid,
        ];
    }
}
