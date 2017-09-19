<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HtmlTemplate extends Model
{
    protected $fillable = [
        'dimensions',
        'text',
        'text_align',
        'font_size',
        'text_color',
        'background_color',
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }
}
