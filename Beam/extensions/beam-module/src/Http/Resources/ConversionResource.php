<?php

namespace Remp\BeamModule\Http\Resources;

use Remp\LaravelHelpers\Resources\JsonResource;

class ConversionResource extends JsonResource
{
    public function toArray($request)
    {
        $values = parent::toArray($request);

        $values['article_external_id'] = $this->article->external_id;

        return $values;
    }
}
