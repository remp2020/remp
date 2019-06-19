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
        'article_id',
        'derived_referer_medium',
        'explicit_referer_medium',
        'count',
        'count_by_referer_host',
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    protected $dates = [
        'time',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
