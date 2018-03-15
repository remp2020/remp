<?php

namespace App\Http\Requests;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    public function __construct(ValidationFactory $validationFactory)
    {
        $validationFactory->extend(
            'required_true_without',
            function ($attr, $val, $params) {
                $other = $params[0];

                if (!$this->get($other) && !$val) {
                    return false;
                }

                return true;
            }
        );
    }

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
            'alt_banner_id' => 'integer|nullable|different:banner_id',
            'signed_in' => 'boolean|nullable',
            'once_per_session' => 'boolean|required',
            'segments' => 'array',
            'pageview_rules.*.num' => 'required_with:pageview_rules.*.rule',
            'pageview_rules.*.rule' => 'required_with:pageview_rules.*.num',
            'target_desktop' => 'required_true_without:target_mobile',
            'target_mobile' => 'required_true_without:target_desktop'
        ];
    }

    public function all($keys = null)
    {
        $data = parent::all($keys);
        if (isset($data['signed_in'])) {
            $data['signed_in'] = $this->getInputSource()->getBoolean('signed_in');
        }
        $data['active'] = $this->getInputSource()->getBoolean('active', false);
        $data['target_desktop'] = $this->getInputSource()->getBoolean('target_desktop', false);
        $data['target_mobile'] = $this->getInputSource()->getBoolean('target_mobile', false);
        $data['once_per_session'] = $this->getInputSource()->getBoolean('once_per_session', false);
        return $data;
    }
}
