<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Model\Conversion;

class ConversionGeneralEvent extends BaseModel
{
    protected $casts = [
        'minutes_to_conversion' => 'integer',
        'event_prior_conversion' => 'integer',
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

    protected $dates = [
        'time',
        'created_at',
        'updated_at',
    ];

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }
}
