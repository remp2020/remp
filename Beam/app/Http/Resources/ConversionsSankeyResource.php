<?php

namespace App\Http\Resources;

use App\Model\Charts\ConversionsSankeyDiagram;
use Remp\LaravelHelpers\Resources\JsonResource;

/**
 * Class SankeyDiagramResource
 *
 * @mixin ConversionsSankeyDiagram
 * @package App\Http\Resources
 */
class ConversionsSankeyResource extends JsonResource
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
            'nodes' => $this->nodes,
            'links' => $this->links,
            'nodeColors' => $this->nodeColors
        ];
    }
}
