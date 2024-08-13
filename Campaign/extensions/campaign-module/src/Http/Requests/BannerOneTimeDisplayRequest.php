<?php

namespace Remp\CampaignModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerOneTimeDisplayRequest extends FormRequest
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
            'user_id' => 'required_without:browser_id',
            'browser_id' => 'required_without:user_id',
            'expires_at' => 'required|date|after:now'
        ];
    }
}
