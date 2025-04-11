<?php

namespace Remp\BeamModule\Http\Resources;

use Illuminate\Support\Arr;
use Remp\LaravelHelpers\Resources\JsonResource;

class SearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $authors = $this->get('authors');
        $articles = $this->get('articles');
        $segments = $this->get('segments');

        return Arr::collapse([
            $this->when($authors->isNotEmpty(), AuthorSearchResource::collection($authors)->toArray(app('request'))),
            $this->when($articles->isNotEmpty(), ArticleSearchResource::collection($articles)->toArray(app('request'))),
            $this->when($segments->isNotEmpty(), SegmentSearchResource::collection($segments)->toArray(app('request')))
        ]);
    }
}
