<?php

namespace Remp\CampaignModule\Http\Resources;

use Remp\LaravelHelpers\Resources\JsonResource;

class SnippetSearchResource extends JsonResource
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
            'type' => 'snippet',
            'name' => $this->name,
            'search_result_url' => route('snippets.edit', $this)
        ];
    }
}
