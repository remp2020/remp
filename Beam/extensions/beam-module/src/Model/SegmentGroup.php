<?php

namespace Remp\BeamModule\Model;

class SegmentGroup extends BaseModel
{
    const CODE_AUTHORS_SEGMENTS = 'authors-segments';
    const CODE_SECTIONS_SEGMENTS = 'sections-segments';
    const CODE_REMP_SEGMENTS = 'remp-segments';

    const TYPE_RULE = 'rule';
    const TYPE_EXPLICIT = 'explicit';

    public static function getByCode($code)
    {
        return SegmentGroup::where('code', $code)->first();
    }

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
