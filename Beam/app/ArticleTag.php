<?php

namespace App;

use App\Model\BaseModel;
use App\Model\Tag;

class ArticleTag extends BaseModel
{
    protected $table = 'article_tag';

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
