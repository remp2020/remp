<?php

namespace Remp\CampaignModule;

use Database\Factories\BarTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarTemplate extends AbstractTemplate
{
    use HasFactory;

    protected $fillable = [
        'main_text',
        'button_text',
        'color_scheme',
    ];

    protected $attributes = [
        'color_scheme' => 'grey',
    ];

    protected static function newFactory(): BarTemplateFactory
    {
        return BarTemplateFactory::new();
    }

    /**
     * Text should return textual representation of the banner's main text in the cleanest possible form.
     * @return mixed
     */
    public function text()
    {
        return strip_tags("{$this->main_text} -- {$this->button_text}");
    }
}
