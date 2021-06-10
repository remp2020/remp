<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Conversion;

class ConversionCommerceEvent extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'minutes_to_conversion' => 'integer',
        'event_prior_conversion' => 'integer',
    ];

    protected $fillable = [
        'time',
        'step',
        'funnel_id',
        'amount',
        'currency',
        'rtm_campaign',
        'rtm_content',
        'rtm_medium',
        'rtm_source',
        'conversion_id',
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

    public function products()
    {
        return $this->hasMany(ConversionCommerceEventProduct::class);
    }
}
