<?php

namespace Remp\CampaignModule;

use Database\Factories\BarTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarTemplate extends AbstractTemplate
{
    /** @use HasFactory<BarTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'main_text',
        'button_text',
        'color_scheme',
    ];

    protected $attributes = [
        'color_scheme' => 'grey',
    ];

    protected $snippetFields = [
        'main_text',
        'button_text',
    ];

    protected static function newFactory(): BarTemplateFactory
    {
        return BarTemplateFactory::new();
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
