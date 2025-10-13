<?php

namespace Remp\CampaignModule;

class OverlayTwoButtonsSignatureTemplate extends AbstractTemplate
{
    protected $fillable = [
        'text_before',
        'text_after',
        'text_btn_primary',
        'text_btn_primary_minor',
        'text_btn_secondary',
        'text_btn_secondary_minor',
        'target_url_secondary',
        'signature_image_url',
        'text_signature'
    ];

    protected $snippetFields = [
        'text_before',
        'text_after',
        'text_btn_primary',
        'text_btn_primary_minor',
        'text_btn_secondary',
        'text_btn_secondary_minor',
        'text_signature'
    ];

    public function text()
    {
        return strip_tags($this->text_before);
    }
}
