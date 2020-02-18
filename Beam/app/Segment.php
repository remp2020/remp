<?php

namespace App;

use App\Model\TableName;
use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;

class Segment extends Model
{
    use TableName, SearchableTrait;

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $attributes = [
        'active' => false,
    ];

    protected $fillable = [
        'name',
        'code',
        'active',
        'segment_group_id'
    ];

    /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [
        /**
         * Columns and their priority in search results.
         * Columns with higher values are more important.
         * Columns with equal values have equal importance.
         *
         * @var array
         */
        'columns' => [
            'segments.name' => 1,
            'segments.code' => 1
        ]
    ];

    public function rules()
    {
        return $this->hasMany(SegmentRule::class);
    }

    public function users()
    {
        return $this->hasMany(SegmentUser::class);
    }

    public function browsers()
    {
        return $this->hasMany(SegmentBrowser::class);
    }

    public function segmentGroup()
    {
        return $this->belongsTo(SegmentGroup::class);
    }
}
