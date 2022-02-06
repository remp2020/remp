<?php

namespace App;

use App\Model\BaseModel;
use App\Model\Tag;
use Illuminate\Database\Eloquent\Builder;

class TagTagCategory extends BaseModel
{
    protected $table = 'tag_tag_category';

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function tagCategory()
    {
        return $this->belongsTo(TagCategory::class);
    }

    public function scopeOfSelectedProperty($query)
    {
        return $query->whereHas('tag', function (Builder $tagQuery) {
            $tagQuery->ofSelectedProperty();
        });
    }
}
