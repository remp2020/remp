<?php

namespace Remp\CampaignModule;

class HtmlTemplate extends AbstractTemplate
{
    protected $fillable = [
        'dimensions',
        'text',
        'css',
        'text_align',
        'font_size',
        'text_color',
        'background_color',
    ];

    protected $snippetFields = [
        'text',
        'css',
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
