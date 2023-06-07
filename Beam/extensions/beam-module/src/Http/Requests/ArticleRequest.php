<?php

namespace Remp\BeamModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
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
            'external_id' => "required|string",
            'property_uuid' => "required|string|exists:properties,uuid",
            'title' => "required|string",
            'url' => "required|url",
            'authors' => 'array',
            'sections' => 'array',
            'tags' => 'array',
            'image_url' => 'nullable|url',
            'published_at' => 'date',

            'authors.*' => 'string',
            'sections.*' => 'string',
        ];
    }
}
