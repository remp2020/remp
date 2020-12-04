<?php

namespace App\Model;

use App\Article;
use App\Conversion;
use Illuminate\Database\Eloquent\Model;

class ConversionPageviewEvent extends Model
{
    protected $casts = [
        'locked' => 'boolean',
        'signed_in' => 'boolean',
        'minutes_to_conversion' => 'integer',
        'event_prior_conversion' => 'integer',
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

    protected $dates = [
        'time',
        'created_at',
        'updated_at',
    ];

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
