<?php

namespace Remp\CampaignModule\Http\Requests;

use Remp\CampaignModule\Banner;
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
            'manual_events_tracking' => 'boolean',
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
                        'dimensions' => 'required',
                    ]);
                    break;
                case Banner::TEMPLATE_MEDIUM_RECTANGLE:
                    $templateValidator = Validator::make($this->all(), [
                        'color_scheme' => 'string|required',
                        'header_text' => 'string|nullable',
                        'main_text' => 'string|nullable',
                        'button_text' => 'string|nullable',
                        'width' => 'string|nullable',
                        'height' => 'string|nullable',
                    ]);
                    break;

                case Banner::TEMPLATE_OVERLAY_RECTANGLE:
                    $templateValidator = Validator::make($this->all(), [
                        'color_scheme' => 'string|required',
                        'header_text' => 'string|nullable',
                        'main_text' => 'string|nullable',
                        'button_text' => 'string|nullable',
                        'width' => 'string|nullable',
                        'height' => 'string|nullable',
                        'image_link' => 'string|nullable',
                    ]);
                    break;
                case Banner::TEMPLATE_OVERLAY_TWO_BUTTONS_SIGNATURE:
                    $templateValidator = Validator::make($this->all(), [
                        'text_before' => 'string|nullable',
                        'text_after' => 'string|nullable',
                        'text_btn_primary' => 'string|required',
                        'text_btn_primary_minor' => 'string|nullable',
                        'text_btn_secondary' => 'string|nullable',
                        'text_btn_secondary_minor' => 'string|nullable',
                        'target_url_secondary' => 'string|nullable',
                        'signature_image_url' => 'string|nullable',
                        'text_signature' => 'string|nullable'
                    ]);
                    break;
                case Banner::TEMPLATE_BAR:
                    $templateValidator = Validator::make($this->all(), [
                        'color_scheme' => 'string|required',
                        'main_text' => 'string|nullable',
                        'button_text' => 'string|nullable',
                    ]);
                    break;
                case Banner::TEMPLATE_COLLAPSIBLE_BAR:
                    $templateValidator = Validator::make($this->all(), [
                        'color_scheme' => 'string|required',
                        'header_text' => 'string|nullable',
                        'collapse_text' => 'string|nullable',
                        'expand_text' => 'string|nullable',
                        'main_text' => 'string|nullable',
                        'button_text' => 'string|nullable',
                        'initial_state' => 'string|required',
                        'force_initial_state' => 'boolean',
                    ]);
                    break;
                case Banner::TEMPLATE_SHORT_MESSAGE:
                    $templateValidator = Validator::make($this->all(), [
                        'color_scheme' => 'string|required',
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
                case Banner::TEMPLATE_NEWSLETTER_RECTANGLE:
                    $templateValidator = Validator::make($this->all(), [
                        'color_scheme' => 'string|required',
                        'newsletter_id' => 'string|required',
                        'btn_submit' => 'string|required',
                        'title' => 'string|nullable',
                        'text' => 'string|nullable',
                        'success' => 'string|nullable',
                        'failure' => 'string|nullable',
                        'terms' => 'string|nullable',
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
        if (!isset($result['manual_events_tracking'])) {
            $result['manual_events_tracking'] = false;
        }
        if (!isset($result['closeable'])) {
            $result['closeable'] = false;
        }

        if (!isset($result['force_initial_state'])) {
            $result['force_initial_state'] = false;
        }
        return $result;
    }
}
