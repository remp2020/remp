<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionGeneralEvent extends BaseModel
{
    protected $casts = [
        'minutes_to_conversion' => 'integer',
        'event_prior_conversion' => 'integer',
        'time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'time',
        'action',
        'category',
        'conversion_id',
        'rtm_campaign',
        'rtm_content',
        'rtm_medium',
        'rtm_source',
        'minutes_to_conversion',
        'event_prior_conversion',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }
}
