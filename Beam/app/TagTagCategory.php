<?php

namespace App;

use App\Model\Tag;
use Illuminate\Database\Eloquent\Model;

class TagTagCategory extends Model
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
