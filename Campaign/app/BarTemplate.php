<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BarTemplate extends Model
{
    protected $fillable = [
        'main_text',
        'button_text',
        'background_color',
        'text_color',
        'button_background_color',
        'button_text_color',
    ];

    protected $touches = [
        'banner',
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }
}
