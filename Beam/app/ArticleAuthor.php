<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticleAuthor extends Model
{
    protected $table = 'article_author';

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
