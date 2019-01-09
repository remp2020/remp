<?php

namespace App\Http\Requests;

use App\Model\NewsletterCriteria;
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
            'timespan' => 'required|integer',
            'articles_count' => 'required|integer',
            'criterias.*' => 'required|string|in:' . NewsletterCriteria::allCriteriaConcatenated(),
            'user_ids.*' => 'required|integer',
        ];
    }
}
