<?php

namespace App;

use App\Model\BaseModel;

class ArticleAuthor extends BaseModel
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
