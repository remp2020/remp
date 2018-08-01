<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ViewsPerBrowserMv extends Model
{
    protected $table = 'views_per_browser_mv';

    public $timestamps = false;

    protected $casts = [
        'total_views' => 'integer',
    ];

    protected $fillable = [
        'browser_id',
        'total_views',
    ];
}
