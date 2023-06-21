<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Database\Factories\ConversionFactory;
use Remp\BeamModule\Model\Article;
use Remp\BeamModule\Model\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Remp\BeamModule\Model\ConversionCommerceEvent;
use Remp\BeamModule\Model\ConversionGeneralEvent;
use Remp\BeamModule\Model\ConversionPageviewEvent;
use Remp\BeamModule\Model\ConversionSource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
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

    protected $dates = [
        'paid_at'
    ];

    protected $casts = [
        'events_aggregated' => 'boolean',
        'source_processed' => 'boolean',
    ];

    protected static function newFactory(): ConversionFactory
    {
        return ConversionFactory::new();
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
    
    public function commerceEvents()
    {
        return $this->hasMany(ConversionCommerceEvent::class);
    }

    public function pageviewEvents()
    {
        return $this->hasMany(ConversionPageviewEvent::class);
    }

    public function generalEvents()
    {
        return $this->hasMany(ConversionGeneralEvent::class);
    }

    public function conversionSources()
    {
        return $this->hasMany(ConversionSource::class);
    }
    
    // Scopes
    
    public function scopeOfSelectedProperty($query)
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
