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
        'initial_state',
        'force_initial_state',
        'color_scheme',
    ];

    protected $attributes = [
        'color_scheme' => 'grey',
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
