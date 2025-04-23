<?php

namespace Remp\BeamModule\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Remp\BeamModule\Database\Factories\ConversionFactory;
use Remp\Journal\TokenProvider;

class Conversion extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'article_external_id',
        'transaction_id',
        'amount',
        'currency',
        'paid_at',
        'user_id',
        'events_aggregated',
    ];

    protected $casts = [
        'events_aggregated' => 'boolean',
        'source_processed' => 'boolean',
        'paid_at' => 'datetime',
    ];

    protected static function newFactory(): ConversionFactory
    {
        return ConversionFactory::new();
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
    
    public function commerceEvents(): HasMany
    {
        return $this->hasMany(ConversionCommerceEvent::class);
    }

    public function pageviewEvents(): HasMany
    {
        return $this->hasMany(ConversionPageviewEvent::class);
    }

    public function generalEvents(): HasMany
    {
        return $this->hasMany(ConversionGeneralEvent::class);
    }

    public function conversionSources(): HasMany
    {
        return $this->hasMany(ConversionSource::class);
    }
    
    // Scopes
    
    public function scopeOfSelectedProperty(Builder $query): Builder
    {
        $tokenProvider = resolve(TokenProvider::class);
        $propertyUuid = $tokenProvider->getToken();
        if ($propertyUuid) {
            $query->whereHas('article', function (Builder $articleQuery) {
                $articleQuery->ofSelectedProperty();
            });
        }
        return $query;
    }

    public function setArticleExternalIdAttribute($articleExternalId)
    {
        $article = Article::select()->where([
            'external_id' => $articleExternalId
        ])->first();
        if (!$article) {
            throw new ModelNotFoundException(sprintf('Unable to link conversion to article %s, no internal record found', $articleExternalId));
        }

        $this->article_id = $article->id;
    }

    public function setPaidAtAttribute($value)
    {
        if (!$value) {
            return;
        }
        $this->attributes['paid_at'] = new Carbon($value);
    }

    public static function getAggregatedConversionsWithoutSource()
    {
        return Conversion::select()
            ->where('events_aggregated', true)
            ->where('source_processed', false)
            ->get();
    }
}
