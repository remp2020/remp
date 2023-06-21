<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\BaseModel;

class DashboardArticle extends BaseModel
{
    protected $fillable = [
        'unique_browsers',
        'last_dashboard_time',
    ];

    protected $hidden = [
        'id',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
