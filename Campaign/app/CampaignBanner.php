<?php

namespace App;

use App\Banner;
use App\Campaign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignBanner extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
