<?php

namespace Remp\CampaignModule;

use Database\Factories\MediumRectangleTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MediumRectangleTemplate extends AbstractTemplate
{
    /** @use HasFactory<MediumRectangleTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'header_text',
        'main_text',
        'button_text',
        'width',
        'height',
        'color_scheme',
    ];

    protected $attributes = [
        'color_scheme' => 'grey',
    ];

    protected static function newFactory(): MediumRectangleTemplateFactory
    {
        return MediumRectangleTemplateFactory::new();
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
