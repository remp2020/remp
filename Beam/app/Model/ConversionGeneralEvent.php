<?php

namespace App\Model;

use App\Article;
use App\Conversion;
use Illuminate\Database\Eloquent\Model;

class ConversionGeneralEvent extends Model
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
