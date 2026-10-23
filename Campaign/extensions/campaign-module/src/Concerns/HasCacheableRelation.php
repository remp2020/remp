<?php

namespace Remp\CampaignModule\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property array $cacheableRelations
 */
trait HasCacheableRelation
{
    /**
     * Counterpart of hydrateFromCache(). Same as toArray(), except it includes only the cacheable relations and keeps
     * dates in the model's format, so the hydrated model reads them without guessing their format.
     */
    public function toCacheArray(): array
    {
        $data = $this->attributesToArray();
        foreach ($data as $key => $value) {
            if ($value !== null && $this->isDateAttribute($key)) {
                $data[$key] = $this->fromDateTime($this->getAttribute($key));
            }
        }

        foreach ($this->cacheableRelations as $relationName => $relatedModelClass) {
            if (!$this->relationLoaded($relationName)) {
                continue;
            }

            $key = Str::snake($relationName);
            $related = $this->getRelation($relationName);
            if ($related instanceof Collection) {
                $data[$key] = $related->map(fn ($model) => self::relatedToCacheArray($model))->all();
            } elseif ($related !== null) {
                $data[$key] = self::relatedToCacheArray($related);
            }
        }

        return $data;
    }

    private static function relatedToCacheArray($model): array
    {
        return method_exists($model, 'toCacheArray') ? $model->toCacheArray() : $model->toArray();
    }

    public function hydrateFromCache(array $cachedData): void
    {
        $this->setRawAttributes($cachedData);
        $this->exists = true;
        $this->wasRecentlyCreated = false;
        self::hideTimestamps($this);

        foreach ($this->cacheableRelations as $relationName => $relatedModelClass) {
            $key = Str::snake($relationName);
            if (!isset($cachedData[$key])) {
                continue;
            }

            $cachedRecord = $cachedData[$key];
            if (is_array($cachedRecord) && !isset($cachedRecord['id'])) {
                $relationRecords = new Collection();
                foreach ($cachedRecord as $relationRecord) {
                    $relationRecords->push(self::relatedFromCache($relatedModelClass, $relationRecord));
                }

                unset($this->$relationName);
                $this->setRelation($relationName, $relationRecords);
            } else {
                unset($this->$relationName);
                $this->setRelation($relationName, self::relatedFromCache($relatedModelClass, $cachedRecord));
            }
        }

        // Arrays and json are already casted in the cached data, we want to avoid double casting. It would cause
        // issues and throw errors.
        $this->casts = array_filter($this->casts, fn ($value) => $value !== 'json' && $value !== 'array');
    }

    private static function relatedFromCache(string $relatedModelClass, array $cachedData): Model
    {
        $relatedModel = new $relatedModelClass();

        if (method_exists($relatedModel, 'hydrateFromCache')) {
            $relatedModel->hydrateFromCache($cachedData);
        } else {
            $relatedModel->setRawAttributes($cachedData);
            $relatedModel->exists = true;
            $relatedModel->wasRecentlyCreated = false;
            self::hideTimestamps($relatedModel);
        }

        return $relatedModel;
    }

    /**
     * Converting timestamps to Carbon and back is the most expensive part of serialization. Hidden timestamps are still
     * readable as attributes.
     */
    private static function hideTimestamps(Model $model): void
    {
        $model->makeHidden([$model->getCreatedAtColumn(), $model->getUpdatedAtColumn()]);
    }
}
