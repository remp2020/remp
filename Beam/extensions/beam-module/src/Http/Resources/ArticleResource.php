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

        $values['authors'] = $this->authors->map(fn($author) => [
            'external_id' => $author->external_id,
            'name' => $author->name,
        ])->all();

        $values['tags'] = $this->tags->map(fn($tag) => [
            'external_id' => $tag->external_id,
            'name' => $tag->name,
            'tag_categories' => $tag->tagCategories->map(fn($category) => [
                'external_id' => $category->external_id,
                'name' => $category->name,
            ])->all(),
        ])->all();

        $values['sections'] = $this->sections->map(fn($section) => [
            'external_id' => $section->external_id,
            'name' => $section->name,
        ])->all();

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
