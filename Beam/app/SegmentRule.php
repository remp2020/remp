<?php

namespace App;

use App\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SegmentRule extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'fields' => 'json',
        'flags' => 'json',
        'timespan' => 'integer',
    ];

    protected $attributes = [
        'fields' => '[]',
        'flags' => '[]',
    ];

    protected $fillable = [
        'id',
        'timespan',
        'count',
        'event_category',
        'event_action',
        'segment_id',
        'operator',
        'fields',
        'flags',
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
