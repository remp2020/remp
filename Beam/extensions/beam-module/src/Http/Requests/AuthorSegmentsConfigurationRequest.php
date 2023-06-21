<?php

namespace Remp\BeamModule\Http\Requests;

use Remp\BeamModule\Model\Config\ConfigNames;
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
            ConfigNames::AUTHOR_SEGMENTS_MIN_VIEWS => 'required|numeric|min:0',
            ConfigNames::AUTHOR_SEGMENTS_MIN_AVERAGE_TIMESPENT => 'required|numeric|min:0',
            ConfigNames::AUTHOR_SEGMENTS_MIN_RATIO => 'required|numeric|between:0,1',
            ConfigNames::AUTHOR_SEGMENTS_DAYS_IN_PAST => 'required|numeric|between:1,90',
        ];
    }
}
