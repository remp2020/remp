<?php

namespace Remp\CampaignModule;

class OverlayRectangleTemplate extends AbstractTemplate
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
        'image_link',
    ];

    public function text()
    {
        return strip_tags("{$this->main_text}");
    }
}
