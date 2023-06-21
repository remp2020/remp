<?php

namespace Remp\BeamModule\Http\Requests;

use Remp\BeamModule\Model\ConversionSource;
use Illuminate\Foundation\Http\FormRequest;

class ConversionsSankeyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tz' => 'timezone|required',
            'interval' => 'required|in:7,30',
            'conversionSourceType' => 'required|in:'.implode(',', ConversionSource::getTypes())
        ];
    }
}
