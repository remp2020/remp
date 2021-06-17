<?php

namespace App;

use App\Model\BaseModel;
use App\Model\Tag;

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
}
