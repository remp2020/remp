<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'article_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    public static function getTypes()
    {
        return [
            self::TYPE_SESSION_LAST,
            self::TYPE_SESSION_FIRST
        ];
    }
}
