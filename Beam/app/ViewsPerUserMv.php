<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/*
 * Class ViewsPerUserMv
 * Temporary entity working with table representing materialized view
 * TODO this will go out after remp#253 issue is closed
 * @package App
 */
class ViewsPerUserMv extends Model
{
    protected $table = 'views_per_user_mv';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'total_views_last_30_days' => 'integer',
        'total_views_last_60_days' => 'integer',
        'total_views_last_90_days' => 'integer',
    ];

    protected $fillable = [
        'user_id',
        'total_views_last_30_days',
        'total_views_last_60_days',
        'total_views_last_90_days',
    ];
}
