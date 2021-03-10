<?php

namespace App;

use App\Model\Tag;
use Illuminate\Database\Eloquent\Model;

class ArticleTag extends Model
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
