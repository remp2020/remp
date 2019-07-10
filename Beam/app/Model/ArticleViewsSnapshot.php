<?php
namespace App\Model;

use App\Article;
use Illuminate\Database\Eloquent\Model;

class ArticleViewsSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'time',
        'property_token',
        'external_article_id',
        'derived_referer_medium',
        'explicit_referer_medium',
        'count',
        'count_by_referer'
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    protected $dates = [
        'time',
    ];
}
