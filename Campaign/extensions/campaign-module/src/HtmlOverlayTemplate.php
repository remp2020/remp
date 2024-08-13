<?php

namespace Remp\CampaignModule;

class HtmlOverlayTemplate extends AbstractTemplate
{
    protected $fillable = [
        'text',
        'css',
        'text_align',
        'font_size',
        'text_color',
        'background_color',
    ];

    public function text()
    {
        return strip_tags($this->text);
    }
}
