<?php

namespace Remp\MailerModule\Replace;

class UtmReplace implements ReplaceInterface
{
    private $utmSource; // newsfilter

    private $utmMedium; // email

    private $utmCampaign; // campaign/email identifier

    private $utmContent; // specific job identifier

    public function __construct($utmSource, $utmMedium, $utmCampaign, $utmContent)
    {
        $this->utmSource = $utmSource;
        $this->utmMedium = $utmMedium;
        $this->utmCampaign = $utmCampaign;
        $this->utmContent = $utmContent;
    }

    public function replace($content)
    {
        // replace params
        $urlString = $this->formatUrlString();
        $content = preg_replace('/<a(.*?)href="([^"#?]*)([^"]*?)"(.*?)>/i', '<a$1href="$2?' . $urlString . '$3"$4>', $content);

        // make sure we don't have two "?" characters in query string
        preg_match_all('/href="([^"]*)"/iU', $content, $matches);
        foreach ($matches[0] as $match) {
            $newHref = substr($match, 0, strpos($match, '?') + 1) . str_replace('?', '&', substr($match, strpos($match, '?') + 1));
            $content = str_replace($match, $newHref, $content);
        }

        return $content;
    }

    private function formatUrlString()
    {
        return "utm_source={$this->utmSource}&utm_medium={$this->utmMedium}&utm_campaign={$this->utmCampaign}&utm_content={$this->utmContent}";
    }
}
