<?php

namespace App;

use App\Banner;
use App\Campaign;
use Illuminate\Database\Eloquent\Model;

class CampaignBanner extends Model
{
    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
