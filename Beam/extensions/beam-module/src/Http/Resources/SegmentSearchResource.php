<?php

namespace Remp\BeamModule\Http\Resources;

use Remp\BeamModule\Model\Segment;
use Remp\LaravelHelpers\Resources\JsonResource;

/**
 * Class SegmentSearchResource
 *
 * @mixin Segment
 * @package App\Http\Resources
 */
class SegmentSearchResource extends JsonResource
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
            'type' => 'segment',
            'name' => $this->name,
            'code' => $this->code,
            'search_result_url' => route('segments.edit', $this)
        ];
    }
}
