<?php

namespace Remp\BeamModule\Http\Requests\Api\v1;

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
            'articles.*.authors' => 'array',
            'articles.*.sections' => 'array',
            'articles.*.tags' => 'array',
            'articles.*.image_url' => 'nullable|url',
            'articles.*.published_at' => 'date',
            'articles.*.content_type' => 'string',

            'articles.*.authors.*' => 'string',
            'articles.*.sections.*' => 'string',
            'articles.*.titles.*' => 'nullable|string',
        ];
    }
}
