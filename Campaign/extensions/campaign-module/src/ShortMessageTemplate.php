<?php

namespace Remp\CampaignModule;

use Database\Factories\ShortMessageTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShortMessageTemplate extends AbstractTemplate
{
    use HasFactory;

    protected $fillable = [
        'text',
        'background_color',
        'text_color',
    ];

    protected static function newFactory(): ShortMessageTemplateFactory
    {
        return ShortMessageTemplateFactory::new();
    }

    /**
     * Text should return textual representation of the banner's main text in the cleanest possible form.
     * @return mixed
     */
    public function text()
    {
        return strip_tags($this->text);
    }
}
