<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SegmentRule extends Model
{
    protected $casts = [
        'fields' => 'array',
    ];

    protected $attributes = [
        'fields' => [],
    ];

    protected $fillable = [
        'timespan',
        'count',
        'event_category',
        'event_action',
        'segment_id',
        'operator',
        'fields',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
