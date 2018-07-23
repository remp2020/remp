<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SegmentGroup extends Model
{
    const CODE_AUTHORS_SEGMENTS = 'authors-segments';
    const CODE_REMP_SEGMENTS = 'remp-segments';

    const TYPE_RULE = 'rule';
    const TYPE_EXPLICIT = 'explicit';

    protected $fillable = [
        'name',
        'code',
        'type',
        'sorting',
    ];

    protected $casts = [
        'sorting' => 'integer',
    ];
}
