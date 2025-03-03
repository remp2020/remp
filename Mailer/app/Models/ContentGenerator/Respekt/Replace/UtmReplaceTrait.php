<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\ContentGenerator\Respekt\Replace;

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

        $matches = [];
        // Split URL between path and params (before and after '?')
        preg_match('/^([^?]*)\??(.*)$/', $hrefUrl, $matches);

        $path = $matches[1];
        $params = explode('&', $matches[2] ?? '');
        $finalParams = [];

        $rtmSourceAdded = $rtmMediumAdded = $rtmCampaignAdded = $rtmContentAdded = false;


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
                    $rtmSourceAdded = true;
                } elseif (strcasecmp($key, 'utm_medium') === 0) {
                    $finalParams[] = "$key={$utmMedium}";
                    $rtmMediumAdded = true;
                } elseif (strcasecmp($key, 'utm_campaign') === 0) {
                    $finalParams[] = "$key={$utmCampaign}";
                    $rtmCampaignAdded = true;
                } elseif (strcasecmp($key, 'utm_content') === 0) {
                    $finalParams[] = "$key={$utmContent}";
                    $rtmContentAdded = true;
                } else {
                    $finalParams[] = "$key=" . $value;
                }
            } else {
                $finalParams[] = $param;
            }
        }

        if (!$rtmSourceAdded) {
            $finalParams[] = "utm_source={$utmSource}";
        }
        if (!$rtmMediumAdded) {
            $finalParams[] = "utm_medium={$utmMedium}";
        }
        if (!$rtmCampaignAdded) {
            $finalParams[] = "utm_campaign={$utmCampaign}";
        }
        if (!$rtmContentAdded) {
            $finalParams[] = "utm_content={$utmContent}";
        }

        return $path . '?' . implode('&', $finalParams);
    }
}
