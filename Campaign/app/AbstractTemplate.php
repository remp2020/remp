<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractTemplate extends Model
{
    protected $touches = [
        'banner',
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }

    /**
     * Text should return textual representation of the banner's main text in the cleanest possible form.
     * @return mixed
     */
    public abstract function text();
}