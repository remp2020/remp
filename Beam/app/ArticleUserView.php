<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

class ArticleUserView extends Model
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
