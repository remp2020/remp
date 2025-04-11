<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Remp\BeamModule\Database\Factories\SegmentRuleFactory;

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

    protected static function newFactory(): SegmentRuleFactory
    {
        return SegmentRuleFactory::new();
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }
}
