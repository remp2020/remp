<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HtmlOverlayTemplate extends AbstractTemplate
{
    protected $fillable = [
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

    public function text()
    {
        return strip_tags($this->text);
    }

    /**
     * @return BelongsTo<Banner, $this>
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }
}
