<?php

namespace App;

use App\Model\BaseModel;

class ArticleSection extends BaseModel
{
    protected $table = 'article_section';

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
