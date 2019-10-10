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

    /**
     * Put UTM parameters into URL parameters
     * Function also respects MailGun Variables (e.g. %recipient.autologin%)
     *
     * @param string $hrefUrl
     *
     * @return string
     */
    public function replaceUrl(string $hrefUrl): string
    {
        $url = html_entity_decode($hrefUrl);

        $matches = [];
        // Split URL between path and params (before and after '?')
        preg_match('/^([^\?]*)\??(.*)$/', $url, $matches);

        $path = $matches[1];
        $params = explode('&', $matches[2] ?? '');
        $finalParams = [];

        $utmSourceAdded = $utmMediumAdded = $utmCampaignAdded = $utmContentAdded = false;

        foreach ($params as $param) {
            if (empty($param)) {
                continue;
            }

            $items = explode('=', $param, 2);

            if (isset($items[1])) {
                $key = $items[0];
                $value = $items[1];

                if (strcasecmp($key, 'utm_source') === 0) {
                    $finalParams[] = "$key={$this->utmSource}";
                    $utmSourceAdded = true;
                } else if (strcasecmp($key, 'utm_medium') === 0) {
                    $finalParams[] = "$key={$this->utmMedium}";
                    $utmMediumAdded = true;
                } else if (strcasecmp($key, 'utm_campaign') === 0) {
                    $finalParams[] = "$key={$this->utmCampaign}";
                    $utmCampaignAdded = true;
                } else if (strcasecmp($key, 'utm_content') === 0) {
                    $finalParams[] = "$key={$this->utmContent}";
                    $utmContentAdded = true;
                } else {
                    $finalParams[] = "$key=" . rawurlencode($value);
                }
            } else {
                $finalParams[] = $param;
            }
        }

        if (!$utmSourceAdded) {
            $finalParams[] = "utm_source={$this->utmSource}";
        }
        if (!$utmMediumAdded) {
            $finalParams[] = "utm_medium={$this->utmMedium}";
        }
        if (!$utmCampaignAdded) {
            $finalParams[] = "utm_campaign={$this->utmCampaign}";
        }
        if (!$utmContentAdded) {
            $finalParams[] = "utm_content={$this->utmContent}";
        }

        return $path . '?' . implode('&', $finalParams);
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
