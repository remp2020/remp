<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\SegmentUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SegmentUser extends BaseModel
{
    use HasFactory;

    use TableName;

    protected $fillable = [
        'segment_id',
        'user_id',
    ];

    protected static function newFactory(): SegmentUserFactory
    {
        return SegmentUserFactory::new();
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
