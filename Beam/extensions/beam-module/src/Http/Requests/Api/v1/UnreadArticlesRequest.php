<?php

namespace Remp\BeamModule\Http\Requests\Api\v1;

use Remp\BeamModule\Helpers\Misc;
use Remp\BeamModule\Model\NewsletterCriterion;
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
            'timespan' => [
                'required',
                'regex:' . Misc::TIMESPAN_VALIDATION_REGEX
            ],
            'articles_count' => 'required|integer',
            'criteria.*' => 'required|string|in:' . NewsletterCriterion::allCriteriaConcatenated(),
            'user_ids.*' => 'required|integer',
            'read_articles_timespan' => [
                'regex:' . Misc::TIMESPAN_VALIDATION_REGEX
            ],
            'ignore_authors.*' => 'string',
            'ignore_content_types.*' => 'string',
        ];
    }
}
