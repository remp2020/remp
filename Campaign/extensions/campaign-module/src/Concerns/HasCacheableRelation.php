<?php

namespace Remp\CampaignModule\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property array $cacheableRelations
 */
trait HasCacheableRelation
{
    public function hydrateFromCache(array $cachedData): void
    {
        $this->setRawAttributes($cachedData);
        $this->exists = true;
        $this->wasRecentlyCreated = false;

        foreach ($this->cacheableRelations as $relationName => $relatedModelClass) {
            $key = Str::snake($relationName);
            if (!isset($cachedData[$key])) {
                continue;
            }

            $cachedRecord = $cachedData[$key];
            if (is_array($cachedRecord) && !isset($cachedRecord['id'])) {
                $relationRecords = new Collection();
                foreach ($cachedRecord as $relationRecord) {
                    $relatedModel = new $relatedModelClass();

                    if (method_exists($relatedModel, 'hydrateFromCache')) {
                        $relatedModel->hydrateFromCache($relationRecord);
                    } else {
                        $relatedModel->setRawAttributes($relationRecord);
                        $relatedModel->exists = true;
                        $relatedModel->wasRecentlyCreated = false;
                    }

                    $relationRecords->push($relatedModel);
                }

                unset($this->$relationName);
                $this->setRelation($relationName, $relationRecords);
            } else {
                $relatedModel = new $relatedModelClass();

                if (method_exists($relatedModel, 'hydrateFromCache')) {
                    $relatedModel->hydrateFromCache($cachedRecord);
                } else {
                    $relatedModel->setRawAttributes($cachedRecord);
                    $relatedModel->exists = true;
                    $relatedModel->wasRecentlyCreated = false;
                }

                unset($this->$relationName);
                $this->setRelation($relationName, $relatedModel);
            }
        }

        // Arrays and json are already casted in the cached data, we want to avoid double casting. It would cause
        // issues and throw errors.
        $this->casts = array_filter($this->casts, fn ($value) => $value !== 'json' && $value !== 'array');
    }
}
