<?php

namespace Remp\CampaignModule;

use Database\Factories\MediumRectangleTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $snippetFields = [
        'header_text',
        'main_text',
        'button_text',
    ];

    protected static function newFactory(): MediumRectangleTemplateFactory
    {
        return MediumRectangleTemplateFactory::new();
    }

    /**
     * @return BelongsTo<Banner, $this>
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
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
