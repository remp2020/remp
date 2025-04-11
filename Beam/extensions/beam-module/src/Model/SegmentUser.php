<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Remp\BeamModule\Database\Factories\SegmentUserFactory;

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

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
