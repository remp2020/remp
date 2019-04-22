<?php

namespace App;

class ShortMessageTemplate extends AbstractTemplate
{
    protected $fillable = [
        'text',
        'background_color',
        'text_color',
    ];

    /**
     * Text should return textual representation of the banner's main text in the cleanest possible form.
     * @return mixed
     */
    public function text()
    {
        return strip_tags($this->text);
    }
}
