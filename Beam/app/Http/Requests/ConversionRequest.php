<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $transactionId = $this->conversion->transaction_id ?? '';

        return [
            'article_external_id' => "required|string|exists:articles,external_id",
            'transaction_id' => "required|string|unique:conversions,transaction_id,{$transactionId},transaction_id",
            'amount' => "required|numeric",
            'currency' => "required|string",
            'paid_at' => "required|date",
        ];
    }

    public function messages()
    {
        return [
            'article_external_id.exists' => "Article with provided external ID doesn't exist",
            'transaction_id.unique' => 'Conversion with given transaction ID has already been recorded',
        ];
    }
}
