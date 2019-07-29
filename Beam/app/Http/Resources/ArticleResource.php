<?php

namespace App\Http\Resources;

use App\Article;
use Remp\LaravelHelpers\Resources\JsonResource;

/**
 * Class ArticleResource
 *
 * @mixin Article
 * @package App\Http\Resources
 */
class ArticleResource extends JsonResource
{
    /**
     * Checks for 'extended' value in request. If true, additional parameters from Article accessors are provided.
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $values = parent::toArray($request);

        $extended = (bool) $request->input('extended', false);
        if ($extended) {
            $values['unique_browsers_count'] = $this->unique_browsers_count;
            $values['conversion_rate'] = $this->conversion_rate;
            $values['renewed_conversions_count'] = $this->renewed_conversions_count;
            $values['new_conversions_count'] = $this->new_conversions_count;
            $values['has_image_variants'] = $this->has_image_variants;
            $values['has_title_variants'] = $this->has_title_variants;
        }

        return $values;
    }
}
