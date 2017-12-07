<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShortMessageTemplate extends Model
{
    protected $fillable = [
        'text',
        'background_color',
        'text_color',
    ];

    protected $touches = [
        'banner',
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }
}
