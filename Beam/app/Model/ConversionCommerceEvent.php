<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ConversionCommerceEvent extends Model
{
    protected $fillable = [
        'time',
        'step',
        'funnel_id',
        'amount',
        'currency',
        'utm_campaign',
        'utm_content',
        'utm_medium',
        'utm_source',
    ];

    protected $dates = [
        'time',
        'created_at',
        'updated_at',
    ];

    public function products()
    {
        return $this->hasMany(ConversionCommerceEventProduct::class);
    }
}
