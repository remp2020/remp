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
        // Strip fragment so rtm_click lands in the query, not buried inside it.
        [$url, $fragment] = self::splitFragment($url);
        $url = self::removeRtmClickHash($url);

        $hashQueryParam = self::HASH_PARAM . '=' . $hash;
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . $hashQueryParam . $fragment;
    }

    public static function getRtmClickHashFromUrl(string $url): ?string
    {
        [$url] = self::splitFragment($url);

        $matches = [];
        // Extracts RTM click hash value from URL query params
        preg_match('/^[^?]*\??.*[?&]' . self::HASH_PARAM . '=([^?&\s]*).*$/m', $url, $matches);

        return empty($matches[1]) ? null : $matches[1];
    }

    public static function removeRtmClickHash(string $url): string
    {
        // Strip fragment so '?' characters inside the fragment aren't mistaken for query separators.
        [$url, $fragment] = self::splitFragment($url);

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
            return $path . $fragment;
        }

        return $path . '?' . implode('&', $finalParams) . $fragment;
    }

    /** @return array{0: string, 1: string} */
    private static function splitFragment(string $url): array
    {
        $fragmentPos = strpos($url, '#');
        if ($fragmentPos === false) {
            return [$url, ''];
        }

        return [substr($url, 0, $fragmentPos), substr($url, $fragmentPos)];
    }
}
