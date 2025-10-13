<?php

namespace Remp\CampaignModule;

use Database\Factories\ShortMessageTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortMessageTemplate extends AbstractTemplate
{
    /** @use HasFactory<ShortMessageTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'text',
        'color_scheme',
    ];

    protected $attributes = [
        'color_scheme' => 'grey',
    ];

    protected $snippetFields = [
        'text',
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

    /**
     * @return BelongsTo<Banner, $this>
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }
}
