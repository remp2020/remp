<?php

namespace App\Http\Requests;

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
            'external_id' => "required|string|unique:articles",
            'property_uuid' => "required|string",
            'title' => "required|string",
            'url' => "required|url",
            'authors' => 'array',
            'sections' => 'array',
            'image_url' => 'url',
            'published_at' => 'date',

            'authors.*' => 'string',
            'sections.*' => 'string',
        ];
    }
}
