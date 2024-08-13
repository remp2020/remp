<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractTemplate extends Model
{
    protected $touches = [
        'banner',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }

    /**
     * Text should return textual representation of the banner's main text in the cleanest possible form.
     * @return mixed
     */
    abstract public function text();
}
