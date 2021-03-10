<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticleSection extends Model
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
