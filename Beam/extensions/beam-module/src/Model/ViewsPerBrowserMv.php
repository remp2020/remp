<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Model\BaseModel;

/**
 * Class ViewsPerBrowserMv
 * Temporary entity working with table representing materialized view
 * TODO this will go out after remp#253 issue is closed
 * @package App
 */
class ViewsPerBrowserMv extends BaseModel
{
    protected $table = 'views_per_browser_mv';

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = [
        'total_views' => 'integer',
    ];

    protected $fillable = [
        'browser_id',
        'total_views',
    ];
}
