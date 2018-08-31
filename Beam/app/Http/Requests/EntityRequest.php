<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntityRequest extends FormRequest
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
        $id = $this->entity->id ?? '';

        return [
            'name' => "required|string|unique:entities,name,{$id}|max:255",
            'parent_id' => 'required|integer',

            'properties.*.name' => 'required|string',
            'properties.*.type' => 'required|string',
        ];
    }
}
