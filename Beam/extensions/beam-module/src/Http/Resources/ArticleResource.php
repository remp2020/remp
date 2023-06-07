<?php

namespace Remp\BeamModule\Http\Resources;

use Remp\BeamModule\Model\Article;
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

        foreach ($this->authors as $author) {
            $values['authors'][] = [
                'id' => $author->id,
                'external_id' => $author->external_id,
            ];
        }

        foreach ($this->tags as $tag) {
            $values['tags'][] = [
                'id' => $tag->id,
                'external_id' => $tag->external_id,
            ];
        }

        foreach ($this->sections as $section) {
            $values['sections'][] = [
                'id' => $section->id,
                'external_id' => $section->external_id,
            ];
        }

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
