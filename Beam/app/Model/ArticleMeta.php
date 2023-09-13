<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\BaseModel;

class ArticleMeta extends BaseModel
{
    protected $table = 'article_meta';

    use HasFactory;

    protected $fillable = [
        'article_id',
        'key',
        'value',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
