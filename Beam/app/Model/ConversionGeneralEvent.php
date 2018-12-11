<?php

namespace App\Model;

use App\Article;
use App\Conversion;
use Illuminate\Database\Eloquent\Model;

class ConversionGeneralEvent extends Model
{
    protected $fillable = [
        'time',
        'action',
        'category',
        'conversion_id',
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

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }
}
