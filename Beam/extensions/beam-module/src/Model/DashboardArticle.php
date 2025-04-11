<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardArticle extends BaseModel
{
    protected $fillable = [
        'unique_browsers',
        'last_dashboard_time',
    ];

    protected $hidden = [
        'id',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
