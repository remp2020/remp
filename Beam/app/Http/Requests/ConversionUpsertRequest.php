<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversionUpsertRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'conversions.*.article_external_id' => "required|string|exists:articles,external_id",
            'conversions.*.transaction_id' => "required|string",
            'conversions.*.amount' => "required|numeric",
            'conversions.*.currency' => "required|string",
            'conversions.*.paid_at' => "required|date",
        ];
    }

    public function messages()
    {
        return [
            'conversions.*.article_external_id.exists' => "Article with provided external ID doesn't exist",
            'conversions.*.transaction_id.unique' => 'Conversion with given transaction ID has already been recorded',
        ];
    }
}
