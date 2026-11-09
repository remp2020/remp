<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceSegmentBrowsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'browser_ids' => 'present|array', // present means empty array is also accepted
            'browser_ids.*' => 'required|string|max:255',
        ];
    }
}
