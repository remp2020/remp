<?php

namespace Remp\CampaignModule;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $snippetFields = [
        'title',
        'text',
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

    public function getEndpointAttribute()
    {
        return $this->attributes['endpoint'] ?? $this->getConfig('endpoint');
    }

    public function getUseXhrAttribute()
    {
        return $this->attributes['use_xhr'] ?? $this->getConfig('use_xhr');
    }

    public function getRequestBodyAttribute()
    {
        return $this->attributes['request_body'] ?? $this->getConfig('request_body');
    }

    public function getRequestHeadersAttribute()
    {
        return $this->attributes['request_headers'] ?? $this->getConfig('request_headers');
    }

    public function getParamsTranspositionAttribute()
    {
        return $this->attributes['params_transposition'] ?? $this->getConfig('params_transposition');
    }

    public function getParamsExtraAttribute()
    {
        return $this->attributes['params_extra'] ?? $this->getConfig('params_extra');
    }

    public function getRempMailerAddrAttribute()
    {
        return $this->attributes['remp_mailer_addr'] ?? $this->getConfig('remp_mailer_addr');
    }

    /**
     * @return BelongsTo<Banner, $this>
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }

    /**
     * Text should return textual representation of the banner's main text in the cleanest possible form.
     * @return mixed
     */
    public function text()
    {
        return strip_tags("({$this->newsletter_id}) {$this->title} -- {$this->text}");
    }

    private function getConfig($key)
    {
        if (empty($this->banner_config)) {
            $this->banner_config = [
                'endpoint' => config('newsletter_banners.endpoint'),
                'use_xhr' => config('newsletter_banners.use_xhr'),
                'request_body' => config('newsletter_banners.request_body'),
                'request_headers' => config('newsletter_banners.request_headers'),
                'params_transposition' => config('newsletter_banners.params_transposition'),
                'params_extra' => config('newsletter_banners.params_extra'),
                'remp_mailer_addr' => config('services.remp.mailer.web_addr')
            ];
        }

        return $this->banner_config[$key] ?? null;
    }
}
