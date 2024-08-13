<?php

namespace Remp\CampaignModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataTable extends FormRequest
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
            'order.0.column',
            'order.0.dir',
            'start',
            'length',
            'search.value',
            'start',
        ];
    }
}
