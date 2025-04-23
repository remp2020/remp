<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Remp\Journal\TokenProvider;

class ArticleTag extends BaseModel
{
    protected $table = 'article_tag';

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function scopeOfSelectedProperty(Builder $query): Builder
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
