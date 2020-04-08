<?php

namespace App;

use App\Model\TableName;
use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Segment extends Model implements Searchable
{
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
