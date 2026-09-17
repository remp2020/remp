<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators\Euobserver;

use Remp\Mailer\Models\Generators\EmbedParser as DefaultEmbedParser;

class EmbedParser extends DefaultEmbedParser
{
    public function createEmbedMarkup(string $link, ?string $title = null, ?string $image = null, bool $isVideo = false): string
    {
        if ($this->isTwitterLink($link)) {
            $html = '';

            if ($imageUrl = $this->fetchXPreviewImage($link)) {
                $html = <<<HTML
<p style="margin: 16px 0 16px 0">
    <img src='{$imageUrl}' alt='X.com post' style='outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:inline;' width='660'>
</p>
HTML;
            }

            $html .= <<<HTML
<div>
    <a href="{$link}" style="display: inline-block; background-color: #fff; color: #000; border: 1px solid #000; font-family: Arial, sans-serif; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 9999px; padding: 8px 24px; white-space: nowrap; text-align: center;">
        {$this->twitterLinkText}
    </a>
</div>
HTML;

            return $html;
        }

        return parent::createEmbedMarkup($link, $title, $image, $isVideo);
    }
}
