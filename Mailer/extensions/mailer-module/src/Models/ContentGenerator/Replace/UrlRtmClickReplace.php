<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Nette\Http\Url;
use Nette\InvalidArgumentException;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

class UrlRtmClickReplace extends RtmClickReplace
{
    public function replace(string $content, GeneratorInput $generatorInput, array $context = null): string
    {
        if (!$this->isEnabled()) {
            return $content;
        }

        // fast check to avoid unnecessary parsing
        if (!str_starts_with($content, 'http')) {
            return $content;
        }

        try {
            $url = new Url($content);
        } catch (InvalidArgumentException $e) {
            return $content;
        }

        $template = $generatorInput->template();
        $hash = $this->computeUrlHash($url, $template->code);
        $url->setQueryParameter(self::HASH_PARAM, $hash);

        if (isset($context['status']) && $context['status'] === 'sending') {
            $this->mailTemplateLinksRepository->upsert($template->id, (clone $url)->setQuery([])->getAbsoluteUrl(), $hash);
        }

        return $url->getAbsoluteUrl();
    }
}
