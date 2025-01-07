<?php

namespace Remp\CampaignModule;

class NewsletterRectangleTemplate extends AbstractTemplate
{
    protected $fillable = [
        'newsletter_id',
        'btn_submit',
        'title',
        'text',
        'success',
        'failure',
        'terms',
        'width',
        'height',
        'color_scheme',
    ];

    protected $attributes = [
        'color_scheme' => 'grey',
    ];

    public $banner_config = [];

    protected $appends = [
        'endpoint',
        'use_xhr',
        'request_body',
        'request_headers',
        'params_transposition',
        'params_extra',
        'remp_mailer_addr'
    ];

    public function __construct(array $attributes = [])
    {
        $this->banner_config = [
            'endpoint' => config('newsletter_banners.endpoint'),
            'use_xhr' => config('newsletter_banners.use_xhr'),
            'request_body' => config('newsletter_banners.request_body'),
            'request_headers' => config('newsletter_banners.request_headers'),
            'params_transposition' => config('newsletter_banners.params_transposition'),
            'params_extra' => config('newsletter_banners.params_extra'),
            'remp_mailer_addr' => config('services.remp.mailer.web_addr')
        ];

        parent::__construct($attributes);
    }

    public function getEndpointAttribute()
    {
        return $this->banner_config['endpoint'];
    }

    public function getUseXhrAttribute()
    {
        return $this->banner_config['use_xhr'];
    }

    public function getRequestBodyAttribute()
    {
        return $this->banner_config['request_body'];
    }

    public function getRequestHeadersAttribute()
    {
        return $this->banner_config['request_headers'];
    }

    public function getParamsTranspositionAttribute()
    {
        return $this->banner_config['params_transposition'];
    }

    public function getParamsExtraAttribute()
    {
        return $this->banner_config['params_extra'];
    }

    public function getRempMailerAddrAttribute()
    {
        return $this->banner_config['remp_mailer_addr'];
    }

    /**
     * Text should return textual representation of the banner's main text in the cleanest possible form.
     * @return mixed
     */
    public function text()
    {
        return strip_tags("({$this->newsletter_id}) {$this->title} -- {$this->text}");
    }
}
