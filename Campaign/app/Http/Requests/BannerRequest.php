<?php

namespace App\Http\Requests;

use App\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Validator;

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
            'target_url' => 'nullable|url',
            'position' => 'nullable|in:top_left,top_right,bottom_left,bottom_right',
            'transition' => 'required|string',
            'display_type' => 'string|required|in:overlay,inline',
            'display_delay' => 'nullable|integer|required|required_if:display_type,overlay',
            'close_timeout' => 'nullable|integer',
            'closeable' => 'boolean',
            'target_selector' => 'nullable|string|required_if:display_type,inline',
            'template' => 'required|string',
            'offset_vertical' => 'required|integer',
            'offset_horizontal' => 'required|integer',
            'js_includes' => 'array',
            'css_includes' => 'array',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator)
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $templateType = $this->get('template');
            switch ($templateType) {
                case Banner::TEMPLATE_HTML:
                    $templateValidator = Validator::make($this->all(), [
                        'text' => 'required',
                        'text_align' => 'required',
                        'text_color' => 'required',
                        'background_color' => 'required',
                        'font_size' => 'required',
                    ]);
                    break;
                case Banner::TEMPLATE_MEDIUM_RECTANGLE:
                    $templateValidator = Validator::make($this->all(), [
                        'background_color' => 'string|required',
                        'text_color' => 'string|required',
                        'button_background_color' => 'string|required',
                        'button_text_color' => 'string|required',
                        'header_text' => 'string|nullable',
                        'main_text' => 'string|required',
                        'button_text' => 'string|required',
                        'width' => 'string|nullable',
                        'height' => 'string|nullable',
                    ]);
                    break;

                case Banner::TEMPLATE_OVERLAY_RECTANGLE:
                    $templateValidator = Validator::make($this->all(), [
                        'background_color' => 'string|required',
                        'text_color' => 'string|required',
                        'button_background_color' => 'string|required',
                        'button_text_color' => 'string|required',
                        'header_text' => 'string|nullable',
                        'main_text' => 'string|nullable',
                        'button_text' => 'string|nullable',
                        'width' => 'string|nullable',
                        'height' => 'string|nullable',
                        'image_link' => 'string|nullable',
                    ]);
                    break;
                case Banner::TEMPLATE_BAR:
                    $templateValidator = Validator::make($this->all(), [
                        'background_color' => 'string|required',
                        'text_color' => 'string|required',
                        'button_background_color' => 'string|required',
                        'button_text_color' => 'string|required',
                        'main_text' => 'string|required',
                        'button_text' => 'string|required',
                    ]);
                    break;
                case Banner::TEMPLATE_COLLAPSIBLE_BAR:
                    $templateValidator = Validator::make($this->all(), [
                        'background_color' => 'string|required',
                        'text_color' => 'string|required',
                        'button_background_color' => 'string|required',
                        'button_text_color' => 'string|required',
                        'header_text' => 'string|required',
                        'collapse_text' => 'string|required',
                        'expand_text' => 'string|required',
                        'main_text' => 'string|required',
                        'button_text' => 'string|required',
                        'initial_state' => 'string|required',
                    ]);
                    break;
                case Banner::TEMPLATE_SHORT_MESSAGE:
                    $templateValidator = Validator::make($this->all(), [
                        'background_color' => 'string|required',
                        'text_color' => 'string|required',
                        'text' => 'string|required',
                    ]);
                    break;
                case Banner::TEMPLATE_HTML_OVERLAY:
                    $templateValidator = Validator::make($this->all(), [
                        'text' => 'required',
                        'text_color' => 'required',
                        'background_color' => 'required',
                    ]);
                    break;
                default:
                    throw new \Exception('unhandled template type: ' . $templateType);
            }
            $templateValidator->validate();
        });
    }

    public function all($keys = null)
    {
        $result = parent::all($keys);
        if (!isset($result['closeable'])) {
            $result['closeable'] = false;
        }
        return $result;
    }
}
