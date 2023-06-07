<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\ArticlePageviewsFactory;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\BeamModule\Model\Aggregable;

class ArticlePageviews extends BaseModel implements Aggregable
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'time_from',
        'time_to',
        'sum',
        'signed_in',
        'subscribers',
    ];

    protected static function newFactory(): ArticlePageviewsFactory
    {
        return ArticlePageviewsFactory::new();
    }

    public function aggregatedFields(): array
    {
        return ['sum', 'signed_in', 'subscribers'];
    }

    public function groupableFields(): array
    {
        return ['article_id'];
    }

    protected $dates = [
        'time_from',
        'time_to',
    ];

    protected $casts = [
        'sum' => 'integer',
        'signed_in' => 'integer',
        'subscribers' => 'integer',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
