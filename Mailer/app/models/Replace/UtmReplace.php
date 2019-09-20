<?php

namespace Remp\MailerModule\Replace;

use Nette\Http\Url;

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

    public function replaceUrl(string $hrefUrl): string
    {
        $url = new Url(html_entity_decode($hrefUrl));
        $url->setQueryParameter('utm_source', $this->utmSource);
        $url->setQueryParameter('utm_medium', $this->utmMedium);
        $url->setQueryParameter('utm_campaign', $this->utmCampaign);
        $url->setQueryParameter('utm_content', $this->utmContent);
        return $url->getAbsoluteUrl();
    }

    public function replace($content)
    {
        $matches = [];
        preg_match_all('/<a(.*?)href="([^"]*?)"(.*?)>/i', $content, $matches);

        if (count($matches) > 0) {
            foreach ($matches[2] as $idx => $hrefUrl) {
                if (strpos($hrefUrl, 'http') === false) {
                    continue;
                }

                $href = sprintf('<a%shref="%s"%s>', $matches[1][$idx], $this->replaceUrl($hrefUrl), $matches[3][$idx]);
                $content = str_replace($matches[0][$idx], $href, $content);
            }
        }

        return $content;
    }
}
