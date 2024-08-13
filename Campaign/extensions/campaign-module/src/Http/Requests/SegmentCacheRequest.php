<?php

namespace Remp\CampaignModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SegmentCacheRequest extends FormRequest
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
            'segment_provider' => 'required|string',
            'segment_code' => 'required|string',
            'user_id' => 'required|integer',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    public function prepareForValidation()
    {
        // add route parameters into form request for validation
        $this->merge([
            'segment_provider' => $this->route('segment_provider'),
            'segment_code' => $this->route('segment_code'),
        ]);
    }
}
