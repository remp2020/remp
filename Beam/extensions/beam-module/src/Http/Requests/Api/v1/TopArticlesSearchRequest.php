<?php

namespace Remp\BeamModule\Http\Requests\Api\v1;

use Illuminate\Support\Arr;
use Illuminate\Foundation\Http\FormRequest;

class TopArticlesSearchRequest extends FormRequest
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
            'from' => 'required|date',
            'limit' => 'required|integer',
            'sections' => ['array', function ($attr, $value, $fail) {
                if (Arr::has($value, ['external_id', 'name'])) {
                    $fail("You can not have both 'external_id' and 'name' arrays specified in sections filter");
                }
                if (!array_key_exists('external_id', $value) && !array_key_exists('name', $value)) {
                    $fail("You have to specify either 'external_id' or 'name' array in sections filter");
                }
            }],
            'sections.external_id' => 'array',
            'sections.external_id.*' => 'string',
            'sections.name' => 'array',
            'sections.name.*' => 'string',
            'authors' => ['array', function ($attr, $value, $fail) {
                if (Arr::has($value, ['external_id', 'name'])) {
                    $fail("You can not have both 'external_id' and 'name' arrays specified in authors filter");
                }
                if (!array_key_exists('external_id', $value) && !array_key_exists('name', $value)) {
                    $fail("You have to specify either 'external_id' or 'name' array in authors filter");
                }
            }],
            'authors.external_id' => 'array',
            'authors.external_id.*' => 'string',
            'authors.name' => 'array',
            'authors.name.*' => 'string',
            'content_type' => 'string',
            'tags' => ['array', function ($attr, $value, $fail) {
                if (Arr::has($value, ['external_id', 'name'])) {
                    $fail("You can not have both 'external_id' and 'name' arrays specified in tags filter");
                }
                if (!array_key_exists('external_id', $value) && !array_key_exists('name', $value)) {
                    $fail("You have to specify either 'external_id' or 'name' array in tags filter");
                }
            }],
            'tags.external_id' => 'array',
            'tags.external_id.*' => 'string',
            'tags.name' => 'array',
            'tags.name.*' => 'string',
            'tag_categories' => ['array', function ($attr, $value, $fail) {
                if (Arr::has($value, ['external_id', 'name'])) {
                    $fail("You can not have both 'external_id' and 'name' arrays specified in tag categories filter");
                }
                if (!array_key_exists('external_id', $value) && !array_key_exists('name', $value)) {
                    $fail("You have to specify either 'external_id' or 'name' array in tag categories filter");
                }
            }],
            'tag_categories.external_id' => 'array',
            'tag_categories.external_id.*' => 'string',
            'tag_categories.name' => 'array',
            'tag_categories.name.*' => 'string',
        ];
    }
}
