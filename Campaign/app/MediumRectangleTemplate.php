<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MediumRectangleTemplate extends Model
{
    protected $fillable = [
        'header_text',
        'main_text',
        'button_text',
        'background_color',
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }
}
