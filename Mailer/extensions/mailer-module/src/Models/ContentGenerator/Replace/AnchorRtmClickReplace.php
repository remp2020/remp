<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Nette\Caching\Cache;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

class AnchorRtmClickReplace extends RtmClickReplace
{
    public function replace(string $content, GeneratorInput $generatorInput, array $context = null): string
    {
        $template = $generatorInput->template();

        if (!$this->isEnabled($template)) {
            return $content;
        }

        // check if HTML
        if ($content === strip_tags($content)) {
            return $content;
        }

        if (isset($context['sendingMode']) && $context['sendingMode'] === 'batch') {
            [$mailContent, $urls] = $this->hashLinkWithCache($content, $template);
        } else {
            [$mailContent, $urls] = $this->hashLinks($content, $template->code);
        }

        if (isset($context['status']) && $context['status'] === 'sending') {
            foreach ($urls as $hash => $url) {
                $this->mailTemplateLinksRepository->upsert($template->id, $url, $hash);
            }
        }

        return $mailContent;
    }

    private function hashLinkWithCache($mailContent, $template): array
    {
        $cacheKey = 'rtmclick_anchor_' . $template->code . '_' . (isset($template->updated_at) ? $template->updated_at->format('U') : time());
        [$hashedContent, $urls] = $this->storage->read($cacheKey);
        if (isset($hashedContent, $urls)) {
            return [$hashedContent, $urls];
        }

        [$hashedContent, $urls] = $this->hashLinks($mailContent, $template->code);
        $this->storage->write($cacheKey, [$hashedContent, $urls], [Cache::Expire => 60]);

        return [$hashedContent, $urls];
    }

    private function hashLinks(string $mailContent, string $templateCode): array
    {
        $matches = [];
        $links = [];
        $matched = preg_match_all('/<a(\s[^>]*)href\s*=\s*([\"\']??)(http[^\"\'>]*?)\2([^>]*)>/iU', $mailContent, $matches);

        if (!$matched) {
            return [$mailContent, $links];
        }

        foreach ($matches[3] as $idx => $hrefUrl) {
            $urlEmptyParams = $this->removeQueryParams($hrefUrl);

            $hash = $this->computeUrlHash($urlEmptyParams, $idx . $templateCode);
            $finalUrl = $this->setRtmClickHashInUrl($hrefUrl, $hash);

            $links[$hash] = $urlEmptyParams;

            $href = sprintf('<a%shref="%s"%s>', $matches[1][$idx], $finalUrl, $matches[4][$idx]);
            $mailContent = preg_replace('/' . preg_quote($matches[0][$idx], '/') . '/i', $href, $mailContent, 1);
        }

        return [$mailContent, $links];
    }
}
