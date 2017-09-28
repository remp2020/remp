<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MediumRectangleTemplate extends Model
{
    protected $fillable = [
        'header_text',
        'main_text',
        'button_text',
        'width',
        'height',
        'background_color',
        'text_color',
        'button_background_color',
        'button_text_color',
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }
}
