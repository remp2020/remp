<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Remp\Journal\TokenProvider;

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
        $tokenProvider = resolve(TokenProvider::class);
        $propertyUuid = $tokenProvider->getToken();
        if ($propertyUuid) {
            $query->whereHas('article', function (Builder $articlesQuery) {
                $articlesQuery->ofSelectedProperty();
            });
        }
        return $query;
    }
}
