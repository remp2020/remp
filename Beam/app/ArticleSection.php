<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Builder;

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

    public function scopeOfSelectedProperty($query)
    {
        return $query->whereHas('article', function (Builder $articlesQuery) {
            $articlesQuery->ofSelectedProperty();
        });
    }
}
