<?php

namespace App\Model;

use App\Article;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'external_id',
    ];

    protected $hidden = [
        'id',
        'pivot',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
