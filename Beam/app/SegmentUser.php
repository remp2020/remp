<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SegmentUser extends Model
{
    protected $fillable = [
        'segment_id',
        'user_id',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
