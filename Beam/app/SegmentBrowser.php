<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Model\TableName;
use Illuminate\Database\Eloquent\Model;

class SegmentBrowser extends Model
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
