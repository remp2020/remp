<?php

namespace Remp\BeamModule\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class ReadArticlesRequest extends FormRequest
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
            'from' => 'date',
            'to' => 'date',
            'user_id' => 'string|required_without:browser_id',
            'browser_id' => 'string|required_without:user_id',
        ];
    }
}
