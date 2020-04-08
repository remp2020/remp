<?php

namespace App\Http\Resources;

use App\Author;
use Remp\LaravelHelpers\Resources\JsonResource;

/**
 * Class AuthorSearchResource
 *
 * @mixin Author
 * @package App\Http\Resources
 */
class AuthorSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'type' => 'author',
            'name' => $this->name,
            'search_result_url' => route('authors.show', $this)
        ];
    }
}
