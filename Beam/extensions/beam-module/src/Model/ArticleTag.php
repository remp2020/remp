<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\BaseModel;
use Remp\BeamModule\Model\Tag;
use Illuminate\Database\Eloquent\Builder;
use Remp\Journal\TokenProvider;

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
        $tokenProvider = resolve(TokenProvider::class);
        $propertyUuid = $tokenProvider->getToken();
        if ($propertyUuid) {
            $query->whereHas('article', function (Builder $articleQuery) {
                $articleQuery->ofSelectedProperty();
            });
        }
        return $query;
    }
}
