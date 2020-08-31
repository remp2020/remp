<?php

namespace App\Http\Requests;

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
                if (array_has($value, ['external_id', 'name'])) {
                    $fail("You can not have both 'external_id' and 'name' arrays specified");
                }
                if (!array_key_exists('external_id', $value) && !array_key_exists('name', $value)) {
                    $fail("You have to specify either 'external_id' or 'name' array");
                }
            }],
            'sections.external_id' => 'array',
            'sections.external_id.*' => 'string',
            'sections.name' => 'array',
            'sections.name.*' => 'string',
            'content_type' => 'string',
        ];
    }
}
