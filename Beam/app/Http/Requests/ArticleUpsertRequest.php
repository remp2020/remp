<?php

namespace App\Http\Requests;

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
            'articles.*.property_uuid' => "required|string",
            'articles.*.title' => "required|string",
            'articles.*.url' => "required|url",
            'articles.*.authors' => 'array',
            'articles.*.sections' => 'array',
            'articles.*.image_url' => 'url',
            'articles.*.published_at' => 'date',

            'articles.*.authors.*' => 'string',
            'articles.*.sections.*' => 'string',
        ];
    }
}
