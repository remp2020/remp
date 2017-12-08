<?php

namespace App\Http\Requests;

use App\ApiToken;
use Illuminate\Foundation\Http\FormRequest;

class ApiTokenRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return ApiToken::$rules;
    }
}
