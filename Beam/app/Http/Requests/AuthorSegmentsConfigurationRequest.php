<?php

namespace App\Http\Requests;

use App\Model\NewsletterCriterion;
use Illuminate\Foundation\Http\FormRequest;

class AuthorSegmentsConfigurationRequest extends FormRequest
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
            'min_views' => 'required|numeric|min:0',
            'min_average_timespent' => 'required|numeric|min:0',
            'min_ratio' => 'required|numeric|between:0,1',
            'days_in_past' => 'required|numeric|between:1,90',
        ];
    }
}
