<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\SegmentRule
 *
 * @property int $id
 * @property int $segment_id
 * @property int $parent_id
 * @property string $event_category
 * @property string $event_action
 * @property int $timespan
 * @property int $count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Segment $segment
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereEventCategory($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereEventAction($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereParentId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereSegmentId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereTimespan($value)
 * @method static \Illuminate\Database\Query\Builder|\App\SegmentRule whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $fields JSON encoded fields
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SegmentRule whereFields($value)
 */
class SegmentRule extends Model
{
    protected $casts = [
        'fields' => 'array',
    ];

    protected $attributes = [
        'fields' => [],
    ];

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }
}
