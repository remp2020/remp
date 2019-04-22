<?php

namespace App;

use App\Model\TableName;
use Illuminate\Database\Eloquent\Model;

class SegmentBrowser extends Model
{
    use TableName;

    protected $fillable = [
        'segment_id',
        'browser',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
