<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Remp\BeamModule\Database\Factories\SegmentFactory;
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

    public function rules(): HasMany
    {
        return $this->hasMany(SegmentRule::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(SegmentUser::class);
    }

    public function browsers(): HasMany
    {
        return $this->hasMany(SegmentBrowser::class);
    }

    public function segmentGroup(): BelongsTo
    {
        return $this->belongsTo(SegmentGroup::class);
    }
}
