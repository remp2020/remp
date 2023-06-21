<?php

namespace Remp\BeamModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SectionSegmentsTestRequest extends FormRequest
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
            'min_views' => 'required|numeric',
            'min_average_timespent' => 'required|numeric',
            'min_ratio' => 'required|numeric',
            'email' => 'required|email',
            'history' => 'required|in:30,60,90',
        ];
    }
}
