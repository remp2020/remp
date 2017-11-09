<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
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
            'name' => 'required|max:255',
            'active' => 'boolean|required',
            'banner_id' => 'integer|required',
            'signed_in' => 'boolean|nullable',
            'once_per_session' => 'boolean|required',
            'segments' => 'array',
        ];
    }

    public function all()
    {
        $data = parent::all();
        if (isset($data['signed_in'])) {
            $data['signed_in'] = $this->getInputSource()->getBoolean('signed_in');
        }
        $data['active'] = $this->getInputSource()->getBoolean('active', false);
        $data['once_per_session'] = $this->getInputSource()->getBoolean('once_per_session', false);
        return $data;
    }
}
