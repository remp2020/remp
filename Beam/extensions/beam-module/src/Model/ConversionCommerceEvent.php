<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Remp\BeamModule\Database\Factories\ConversionCommerceEventFactory;

class ConversionCommerceEvent extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'minutes_to_conversion' => 'integer',
        'event_prior_conversion' => 'integer',
        'time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    protected static function newFactory(): ConversionCommerceEventFactory
    {
        return ConversionCommerceEventFactory::new();
    }

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(ConversionCommerceEventProduct::class);
    }
}
