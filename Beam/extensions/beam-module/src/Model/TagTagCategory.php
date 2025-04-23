<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Remp\Journal\TokenProvider;

class TagTagCategory extends BaseModel
{
    protected $table = 'tag_tag_category';

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function tagCategory(): BelongsTo
    {
        return $this->belongsTo(TagCategory::class);
    }

    public function scopeOfSelectedProperty(Builder $query): Builder
    {
        $tokenProvider = resolve(TokenProvider::class);
        $propertyUuid = $tokenProvider->getToken();
        if ($propertyUuid) {
            $query->whereHas('tag', function (Builder $tagQuery) {
                /** @var Builder|Tag $tagQuery */
                $tagQuery->ofSelectedProperty();
            });
        }
        return $query;
    }
}
