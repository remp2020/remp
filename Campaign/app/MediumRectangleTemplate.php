<?php

namespace App;

class MediumRectangleTemplate extends AbstractTemplate
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

    /**
     * Text should return textual representation of the banner's main text in the cleanest possible form.
     * @return mixed
     */
    public function text()
    {
        return strip_tags("{$this->main_text} -- {$this->button_text}");
    }
}
