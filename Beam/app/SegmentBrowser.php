<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Model\TableName;

class SegmentBrowser extends BaseModel
{
    use HasFactory;

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
