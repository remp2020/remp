<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Model\TableName;

class SegmentUser extends BaseModel
{
    use HasFactory;

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
