<?php

namespace App\Http\Requests;

use App\Http\Controllers\NewsletterController;
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
            'segment' => 'required|string',
            'mail_type_code' => 'required|string',
            'mailer_generator_id' => 'required|integer',
            'criteria' => 'required|string|in:' . NewsletterController::allCriteriaConcatenated(),
            'articles_count' => 'required|integer|min:1|max:100',
            'starts_at' => 'required|date',
            'timespan' => 'required|integer',
            'email_subject' => 'required|string',
            'email_from' => 'required|string',
            'recurrence_rule' => 'nullable|string',
        ];
    }
}
