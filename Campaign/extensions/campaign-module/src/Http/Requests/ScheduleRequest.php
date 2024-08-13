<?php

namespace Remp\CampaignModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'campaign_id' => 'required|integer',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time'
        ];
    }
}
