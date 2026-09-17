<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

class N3EmbedParser extends EmbedParser
{
    public function createEmbedMarkup(string $link, ?string $title = null, ?string $image = null, bool $isVideo = false): string
    {
        if ($this->isTwitterLink($link)) {
            $html = '';

            if ($imageUrl = $this->fetchXPreviewImage($link)) {
                $html .= "<p style=\"margin: 16px 0 16px 0\"><img src=\"{$imageUrl}\" alt=\"X (Twitter) post\" style=\"outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;max-width:100%;clear:both;display:inline;width:100%;height:auto;\"></p>\n";
            }

            $html .= "<p>{{ include('dn3-button-outline', {\"href\": \"{$link}\", \"text\": \"$this->twitterLinkText\"} ) }}</p>\n\n";

            return $html;
        }

        return parent::createEmbedMarkup($link, $title, $image, $isVideo);
    }
}
