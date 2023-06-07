<?php

namespace Remp\BeamModule\Model;

use Yadakhov\InsertOnDuplicateKey;

class ArticleAggregatedView extends BaseModel
{
    use InsertOnDuplicateKey;

    public $timestamps = false;

    protected $casts = [
        'pageviews' => 'integer',
        'timespent' => 'integer',
    ];

    protected $fillable = [
        'article_id',
        'user_id',
        'browser_id',
        'date',
        'pageviews',
        'timespent',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function articleAuthors()
    {
        return $this->hasMany(ArticleAuthor::class, 'article_id', 'article_id');
    }
}
