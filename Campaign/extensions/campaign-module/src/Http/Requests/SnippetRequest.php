<?php

namespace Remp\CampaignModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SnippetRequest extends FormRequest
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

    public function rules()
    {
        return [
            'name' => [
                "required",
                Rule::unique("snippets", "name")->ignore($this->snippet->id ?? null)
            ],
            'value' => 'required',
        ];
    }
}
