<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use InvalidArgumentException;
use Nette\Caching\Cache;
use Nette\Http\Url;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

class AnchorRtmClickReplace extends RtmClickReplace
{
    public function replace(string $content, GeneratorInput $generatorInput, array $context = null): string
    {
        if (!$this->isEnabled()) {
            return $content;
        }

        if (isset($context['contentType']) && $context['contentType'] === 'params') {
            return $content;
        }

        // check if HTML
        if ($content === strip_tags($content)) {
            return $content;
        }

        $template = $generatorInput->template();
        [$mailContent, $urls] = $this->process($content, $template);

        if (isset($context['status']) && $context['status'] === 'sending') {
            foreach ($urls as $hash => $url) {
                $this->mailTemplateLinksRepository->upsert($template->id, $url->getAbsoluteUrl(), $hash);
            }
        }

        return $mailContent;
    }

    private function process($mailContent, $template): array
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
        preg_match_all('/<a(\s[^>]*)href\s*=\s*([\"\']??)(http[^\" >]*?)\2([^>]*)>/iU', $mailContent, $matches);

        if (empty($matches)) {
            return [$mailContent, $links];
        }

        foreach ($matches[3] as $idx => $hrefUrl) {
            try {
                $url = new Url($hrefUrl);
            } catch (InvalidArgumentException $e) {
                continue;
            }

            $hash = $this->computeUrlHash($url, $idx . $templateCode);
            $url->setQueryParameter(self::HASH_PARAM, $hash);

            $links[$hash] = (clone $url)->setQuery([]);

            $href = sprintf('<a%shref="%s"%s>', $matches[1][$idx], $url->getAbsoluteUrl(), $matches[4][$idx]);
            $mailContent = preg_replace('/' . preg_quote($matches[0][$idx], '/') . '/i', $href, $mailContent, 1);
        }

        return [$mailContent, $links];
    }
}
