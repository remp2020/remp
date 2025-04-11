<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionPageviewEvent extends BaseModel
{
    protected $casts = [
        'locked' => 'boolean',
        'signed_in' => 'boolean',
        'minutes_to_conversion' => 'integer',
        'event_prior_conversion' => 'integer',
        'time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'time',
        'article_id',
        'locked',
        'signed_in',
        'timespent',
        'rtm_campaign',
        'rtm_content',
        'rtm_medium',
        'rtm_source',
        'conversion_id',
        'minutes_to_conversion',
        'event_prior_conversion',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
