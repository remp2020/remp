<?php

namespace Remp\BeamModule\Http\Requests;

use Remp\BeamModule\Helpers\Misc;
use Remp\BeamModule\Model\NewsletterCriterion;
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
            'criteria' => 'required|string|in:' . NewsletterCriterion::allCriteriaConcatenated(),
            'articles_count' => 'required|integer|min:1|max:100',
            'personalized_content' => 'boolean',
            'starts_at' => 'required|date',
            'timespan' => [
                'required',
                'regex:' . Misc::TIMESPAN_VALIDATION_REGEX
            ],
            'email_subject' => 'required|string',
            'email_from' => 'required|string',
            'recurrence_rule' => 'nullable|string',
        ];
    }
}
