<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
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
            'text' => 'required',
            'target_url' => 'required|url',
            'text_align' => 'required',
            'text_color' => 'required',
            'background_color' => 'required',
            'font_size' => 'required',
            'dimensions' => 'required|in:landscape,medium_rectangle',
            'position' => 'required|in:top_left,top_right,bottom_left,bottom_right,middle_left,middle_right',
            'display_delay' => 'integer|required',
            'close_timeout' => 'nullable|integer',
            'closeable' => 'boolean|required'
        ];
    }

    public function all()
    {
        $result = parent::all();
        if (!isset($result['closeable'])) {
            $result['closeable'] = false;
        }
        return $result;
    }
}
