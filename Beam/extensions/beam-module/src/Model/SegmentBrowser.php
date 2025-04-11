<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Remp\BeamModule\Database\Factories\SegmentBrowserFactory;

class SegmentBrowser extends BaseModel
{
    use HasFactory;

    use TableName;

    protected $fillable = [
        'segment_id',
        'browser',
    ];

    protected static function newFactory(): SegmentBrowserFactory
    {
        return SegmentBrowserFactory::new();
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
