<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsletterRequest extends FormRequest
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
            'name' => 'required|string',
            'segment_code' => 'required|string',
            'mailer_generator_id' => 'required|integer',
            'criteria' => 'required|string|in:pageviews,timespent,conversion,average_payment',
            'articles_count' => 'required|integer|min:1|max:100',
            'starts_at' => 'required|date',
            'recurrence_rule' => 'string',
        ];
    }
}
