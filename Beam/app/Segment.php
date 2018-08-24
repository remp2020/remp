<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
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
