<?php

namespace Remp\BeamModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SegmentRequest extends FormRequest
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
        $id = $this->segment->id ?? '';

        return [
            'name' => "required|string|unique:segments,name,{$id}|max:255",
            'code' => "required|string|unique:segments,code,{$id}",
            'active' => 'required|boolean',

            'rules' => 'required|array',
            'rules.*.timespan' => 'required|integer',
            'rules.*.count' => 'required|integer',
            'rules.*.event_category' => 'required|string',
            'rules.*.event_action' => 'required|string',
            'rules.*.operator' => 'required|string',
            'rules.*.fields' => 'array',
            'rules.*.flags' => 'array',
            'rules.*.flags.*.key' => 'string',
            'rules.*.flags.*.value' => 'string|nullable|in:"0","1"',
        ];
    }
}
