<?php

namespace App\Model;

use App\Article;
use Illuminate\Database\Eloquent\Model;

class ConversionPageviewEvent extends Model
{
    protected $fillable = [
        'time',
        'article_id',
        'locked',
        'timespent',
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

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
