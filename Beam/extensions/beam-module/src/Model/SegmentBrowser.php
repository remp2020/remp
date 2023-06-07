<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\SegmentBrowserFactory;
use Remp\BeamModule\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\BeamModule\Model\Segment;
use Remp\BeamModule\Model\TableName;

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

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
