<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\SegmentFactory;
use Remp\BeamModule\Model\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\BeamModule\Model\TableName;
use Remp\BeamModule\Model\SegmentBrowser;
use Remp\BeamModule\Model\SegmentGroup;
use Remp\BeamModule\Model\SegmentRule;
use Remp\BeamModule\Model\SegmentUser;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Segment extends BaseModel implements Searchable
{
    use HasFactory;

    use TableName;

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

    protected static function newFactory(): SegmentFactory
    {
        return SegmentFactory::new();
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }

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
