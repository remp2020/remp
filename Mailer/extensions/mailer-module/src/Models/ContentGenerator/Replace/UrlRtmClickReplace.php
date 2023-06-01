<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

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

        $template = $generatorInput->template();
        $urlEmptyParams = $this->removeQueryParams($content);
        $hash = $this->computeUrlHash($urlEmptyParams, $template->code);
        $finalUrl = $this->setRtmClickHashInUrl($content, $hash);

        if (isset($context['status']) && $context['status'] === 'sending') {
            $this->mailTemplateLinksRepository->upsert($template->id, $urlEmptyParams, $hash);
        }

        return $finalUrl;
    }
}
