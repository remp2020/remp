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
        $validationArray = [
            'name' => 'required|max:255',
            'active' => 'boolean|required',
            'banner_id' => 'integer|required',
            'alt_banner_id' => 'integer|nullable|different:banner_id',
            'signed_in' => 'boolean|nullable',
            'once_per_session' => 'boolean|required',
            'segments' => 'array',
            'pageview_rules.0.num' => 'required_with:pageview_rules.0.rule',
            'pageview_rules.0.rule' => 'required_with:pageview_rules.0.num',
        ];

        foreach($this->request->get('pageview_rules') as $key => $val) {
            $validationArray['pageview_rules.' . $key . '.num'] = 'required_with:pageview_rules.' . $key . '.rule';
            $validationArray['pageview_rules.' . $key . '.rule'] = 'required_with:pageview_rules.' . $key . '.num';
        }

        return $validationArray;
    }

    public function all($keys = null)
    {
        $data = parent::all($keys);
        if (isset($data['signed_in'])) {
            $data['signed_in'] = $this->getInputSource()->getBoolean('signed_in');
        }
        $data['active'] = $this->getInputSource()->getBoolean('active', false);
        $data['once_per_session'] = $this->getInputSource()->getBoolean('once_per_session', false);
        return $data;
    }
}
