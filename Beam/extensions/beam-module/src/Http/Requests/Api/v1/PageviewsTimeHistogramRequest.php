<?php

namespace Remp\BeamModule\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class PageviewsTimeHistogramRequest extends FormRequest
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
            'to' => 'required|date',
            'content_type' => 'string',
            'sections.*' => ['array', function ($attr, $value, $fail) {
                if (Arr::has($value, ['external_ids', 'names'])) {
                    $fail("You can not have both 'external_ids' and 'names' arrays specified in sections filter");
                }
                if (!array_key_exists('external_ids', $value) && !array_key_exists('names', $value)) {
                    $fail("You have to specify either 'external_ids' or 'names' array in sections filter");
                }
            }],
            'sections.*.external_ids' => 'array',
            'sections.*.external_ids.*' => 'string',
            'sections.*.names' => 'array',
            'sections.*.names.*' => 'string',
            'authors.*' => ['array', function ($attr, $value, $fail) {
                if (Arr::has($value, ['external_ids', 'names'])) {
                    $fail("You can not have both 'external_ids' and 'names' arrays specified in authors filter");
                }
                if (!array_key_exists('external_ids', $value) && !array_key_exists('names', $value)) {
                    $fail("You have to specify either 'external_ids' or 'names' array in authors filter");
                }
            }],
            'authors.*.external_ids' => 'array',
            'authors.*.external_ids.*' => 'string',
            'authors.*.names' => 'array',
            'authors.*.names.*' => 'string',
            'tags.*' => ['array', function ($attr, $value, $fail) {
                if (Arr::has($value, ['external_ids', 'names'])) {
                    $fail("You can not have both 'external_ids' and 'names' arrays specified in tags filter");
                }
                if (!array_key_exists('external_ids', $value) && !array_key_exists('names', $value)) {
                    $fail("You have to specify either 'external_ids' or 'names' array in tags filter");
                }
            }],
            'tags.*.external_ids' => 'array',
            'tags.*.external_ids.*' => 'string',
            'tags.*.names' => 'array',
            'tags.*.names.*' => 'string',
            'tag_categories.*' => ['array', function ($attr, $value, $fail) {
                if (Arr::has($value, ['external_ids', 'names'])) {
                    $fail("You can not have both 'external_ids' and 'names' arrays specified in tag categories filter");
                }
                if (!array_key_exists('external_ids', $value) && !array_key_exists('names', $value)) {
                    $fail("You have to specify either 'external_ids' or 'names' array in tag categories filter");
                }
            }],
            'tag_categories.*.external_ids' => 'array',
            'tag_categories.*.external_ids.*' => 'string',
            'tag_categories.*.names' => 'array',
            'tag_categories.*.names.*' => 'string',
        ];
    }
}
