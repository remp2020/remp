<?php

namespace Remp\CampaignModule;

class CollapsibleBarTemplate extends AbstractTemplate
{
    protected $fillable = [
        'main_text',
        'header_text',
        'collapse_text',
        'expand_text',
        'button_text',
        'background_color',
        'text_color',
        'button_background_color',
        'button_text_color',
        'initial_state',
        'force_initial_state',
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
