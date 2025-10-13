<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverlayRectangleTemplate extends AbstractTemplate
{
    protected $fillable = [
        'header_text',
        'main_text',
        'button_text',
        'width',
        'height',
        'image_link',
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

    public function text()
    {
        return strip_tags("{$this->main_text}");
    }

    /**
     * @return BelongsTo<Banner, $this>
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }
}
