<?php

namespace Remp\BeamModule\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;

class ArticleUpsertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'articles.*.external_id' => "required|string",
            'articles.*.property_uuid' => "required|string|exists:properties,uuid",
            'articles.*.title' => "required|string",
            'articles.*.url' => "required|url",
            'articles.*.image_url' => 'nullable|url',
            'articles.*.published_at' => 'required|date',
            'articles.*.titles.*' => 'nullable|string',
            'articles.*.content_type' => 'string',

            // authors
            'articles.*.authors' => 'array',
            'articles.*.authors.*' => 'array',
            'articles.*.authors.*.external_id' => 'required|string',
            'articles.*.authors.*.name' => 'required|string',

            // sections
            'articles.*.sections' => 'array',
            'articles.*.sections.*' => 'array',
            'articles.*.sections.*.external_id' => 'required|string',
            'articles.*.sections.*.name' => 'required|string',

            // tags
            'articles.*.tags' => 'array',
            'articles.*.tags.*' => 'array',
            'articles.*.tags.*.external_id' => 'required|string',
            'articles.*.tags.*.name' => 'required|string',
            'articles.*.tags.*.categories' => 'array',
            'articles.*.tags.*.categories.*' => 'array',
            'articles.*.tags.*.categories.*.external_id' => 'required|string',
            'articles.*.tags.*.categories.*.name' => 'required|string',
        ];
    }
}
