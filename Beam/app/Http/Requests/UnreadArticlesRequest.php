<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnreadArticlesRequest extends FormRequest
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
            'days_span' => 'required|integer',
            'top_count' => 'required|integer',
            'user_ids.*' => 'required|integer',
        ];
    }
}
