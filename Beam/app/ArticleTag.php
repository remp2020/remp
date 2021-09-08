<?php

namespace App;

use App\Model\BaseModel;
use App\Model\Tag;
use Illuminate\Database\Eloquent\Builder;

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

    public function scopeOfSelectedProperty($query)
    {
        return $query->whereHas('article', function (Builder $articleQuery) {
            $articleQuery->ofSelectedProperty();
        });
    }
}
