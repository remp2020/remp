<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class AbstractTemplate extends Model
{
    protected $touches = [
        'banner',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * @var array<string>
     */
    protected $snippetFields = [];

    public function getSnippetFields(): array
    {
        return $this->snippetFields;
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
    abstract public function text();
}
