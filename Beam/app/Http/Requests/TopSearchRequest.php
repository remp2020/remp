<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;
use Illuminate\Foundation\Http\FormRequest;

class TopSearchRequest extends FormRequest
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
        ];
    }
}
