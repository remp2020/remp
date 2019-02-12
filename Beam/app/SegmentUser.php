<?php

namespace App;

use App\Model\TableName;
use Illuminate\Database\Eloquent\Model;

class SegmentUser extends Model
{
    use TableName;

    protected $fillable = [
        'segment_id',
        'user_id',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
