<?php

namespace Remp\CampaignModule\Http\Resources;

use Illuminate\Http\Request;
use Remp\CampaignModule\Snippet;
use Remp\LaravelHelpers\Resources\JsonResource;

/**
 * @mixin Snippet
 */
class SnippetSearchResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'type' => 'snippet',
            'name' => $this->name,
            'search_result_url' => route('snippets.edit', $this)
        ];
    }
}
