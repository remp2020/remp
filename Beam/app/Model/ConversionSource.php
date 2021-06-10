<?php

namespace App\Model;

use App\Conversion;

class ConversionSource extends BaseModel
{
    // source determined based on the first pageview of session prior to the payment
    public const TYPE_SESSION_FIRST = 'session_first';
    // source determined based on the last pageview of session prior to the payment
    public const TYPE_SESSION_LAST = 'session_last';

    protected $fillable = [
        'conversion_id',
        'type',
        'referer_medium',
        'referer_source',
        'referer_host_with_path',
        'pageview_article_external_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }
}
