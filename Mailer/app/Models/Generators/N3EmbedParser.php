<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

class N3EmbedParser extends EmbedParser
{
    public function createEmbedMarkup(string $link, ?string $title = null, ?string $image = null, bool $isVideo = false): string
    {
        if ($this->isTwitterLink($link)) {
            return "<p>{{ include('dn3-button-outline', {\"href\": \"{$link}\", \"text\": \"$this->twitterLinkText\"} ) }}</p>\n\n";
        }

        return parent::createEmbedMarkup($link, $title, $image, $isVideo);
    }
}
