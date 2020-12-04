<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

trait UtmReplaceTrait
{
    /**
     * Put UTM parameters into URL parameters
     * Function also respects MailGun Variables (e.g. %recipient.autologin%)
     *
     * @param string $hrefUrl
     *
     * @param GeneratorInput $generatorInput
     * @return string
     */
    public function replaceUrl(string $hrefUrl, GeneratorInput $generatorInput): string
    {
        $utmSource = $generatorInput->template()->mail_type->code; // !! could be maybe performance issue?
        $utmMedium = 'email';
        $utmCampaign = $generatorInput->template()->code;
        $utmContent = $generatorInput->batchId();

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
                    $finalParams[] = "$key={$utmSource}";
                    $utmSourceAdded = true;
                } elseif (strcasecmp($key, 'utm_medium') === 0) {
                    $finalParams[] = "$key={$utmMedium}";
                    $utmMediumAdded = true;
                } elseif (strcasecmp($key, 'utm_campaign') === 0) {
                    $finalParams[] = "$key={$utmCampaign}";
                    $utmCampaignAdded = true;
                } elseif (strcasecmp($key, 'utm_content') === 0) {
                    $finalParams[] = "$key={$utmContent}";
                    $utmContentAdded = true;
                } else {
                    $finalParams[] = "$key=" . rawurlencode($value);
                }
            } else {
                $finalParams[] = $param;
            }
        }

        if (!$utmSourceAdded) {
            $finalParams[] = "utm_source={$utmSource}";
        }
        if (!$utmMediumAdded) {
            $finalParams[] = "utm_medium={$utmMedium}";
        }
        if (!$utmCampaignAdded) {
            $finalParams[] = "utm_campaign={$utmCampaign}";
        }
        if (!$utmContentAdded) {
            $finalParams[] = "utm_content={$utmContent}";
        }

        return $path . '?' . implode('&', $finalParams);
    }

    public function replace(string $content): string
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
