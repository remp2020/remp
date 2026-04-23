<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators\Euobserver;

use Remp\Mailer\Models\Generators\EmbedParser as DefaultEmbedParser;

class EmbedParser extends DefaultEmbedParser
{
    public function createEmbedMarkup(string $link, ?string $title = null, ?string $image = null, bool $isVideo = false): string
    {
        if ($this->isTwitterLink($link)) {
            return <<<HTML
<a href="{$link}" style="display: inline-block; background-color: #fff; color: #000; border: 1px solid #000; font-family: Arial, sans-serif; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 9999px; padding: 8px 24px; white-space: nowrap; text-align: center;">
    {$this->twitterLinkText}
</a>
HTML;
        }

        return parent::createEmbedMarkup($link, $title, $image, $isVideo);
    }
}
