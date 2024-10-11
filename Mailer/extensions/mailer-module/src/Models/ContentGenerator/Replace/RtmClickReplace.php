<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Repositories\MailTemplateLinksRepository;

abstract class RtmClickReplace implements IReplace
{
    public const HASH_PARAM = 'rtm_click';
    public const CONFIG_NAME = 'mail_click_tracker';

    public function __construct(
        protected MailTemplateLinksRepository $mailTemplateLinksRepository,
        protected Config $config,
        protected Storage $storage
    ) {
    }

    protected function isEnabled(ActiveRow $template): bool
    {
        return $this->config->get(self::CONFIG_NAME) && ($template->click_tracking === null || $template->click_tracking);
    }

    protected function computeUrlHash(string $url, string $additionalInfo = ''): string
    {
        return hash('crc32c', $url . $additionalInfo);
    }

    public function removeQueryParams(string $url): string
    {
        return explode('?', $url)[0];
    }

    public function setRtmClickHashInUrl(string $url, string $hash): string
    {
        $url = self::removeRtmClickHash($url);
        $hashQueryParam = self::HASH_PARAM . '=' . $hash;

        // url already has query params
        if (isset(explode('?', $url)[1])) {
            return "{$url}&{$hashQueryParam}";
        }

        return "{$url}?{$hashQueryParam}";
    }

    public static function getRtmClickHashFromUrl(string $url): ?string
    {
        $matches = [];
        // Extracts RTM click hash value from URL query params
        preg_match('/^[^?]*\??.*[?&]' . self::HASH_PARAM . '=([^?&\s]*).*$/m', $url, $matches);

        return empty($matches[1]) ? null : $matches[1];
    }

    public static function removeRtmClickHash(string $url): string
    {
        $matches = [];
        // Split URL between path and params (before and after '?')
        preg_match('/^([^?]*)\??(.*)$/', $url, $matches);

        $path = $matches[1];
        $params = explode('&', $matches[2] ?? '');
        $finalParams = [];

        foreach ($params as $param) {
            if (empty($param)) {
                continue;
            }

            $items = explode('=', $param, 2);

            if (strcasecmp($items[0], self::HASH_PARAM) === 0) {
                continue;
            }

            $finalParams[] = $param;
        }

        if (empty($finalParams)) {
            return $path;
        }

        return $path . '?' . implode('&', $finalParams);
    }
}
