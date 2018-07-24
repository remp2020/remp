<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SegmentBrowser extends Model
{
    protected $fillable = [
        'segment_id',
        'browser',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
