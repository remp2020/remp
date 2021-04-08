<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Embed\Embed;

class EmbedParser
{
    private $videoLinkText;

    public function setVideoLinkText(?string $videoLinkText = null): void
    {
        $this->videoLinkText = $videoLinkText;
    }

    private function fetch(string $url): ?array
    {
        $embed = new Embed();
        $embed = $embed->get($url);

        $oEmbed = $embed->getOEmbed();
        $type = $oEmbed->get('type');

        $image = null;
        if ($embed->image) {
            $image = $embed->image->__toString();
        }

        return ($embed->url === null) ? null : [
            'link' => $embed->url->__toString(),
            'title' => $embed->title ?? '',
            'image' => $image,
            'isVideo' => $type === 'video'
        ];
    }

    public function parse(string $link): ?string
    {
        $link = trim($link);

        if (preg_match('/^(?:(?:https?:)?\/\/)?(?:www\.)?facebook\.com\/[a-zA-Z0-9\.]+\/videos\/(?:[a-zA-Z0-9\.]+\/)?([0-9]+)/', $link)
            || preg_match('/youtu/', $link)
            || preg_match('/twitt/', $link)
        ) {
            if ($data = $this->fetch($link)) {
                return $this->createEmbedMarkup($data['link'], $data['title'], $data['image'], $data['isVideo']);
            }
        }

        return null;
    }

    public function createEmbedMarkup(string $link, string $title, ?string $image = null, bool $isVideo = false): string
    {
        $html = "<br>";

        if ($isVideo) {
            $html .= "<p style='text-align: center; font-weight: normal;'><i>{$this->videoLinkText}</i></p><br>";
        }

        $html .= "<a href='{$link}' target='_blank' style='color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-decoration:none;text-align: center; display: block;'>";

        if (!is_null($image)) {
            $html .= "<img src='{$image}' alt='{$title}' style='outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:inline;'>";
        } else {
            $html .= "<span style='text-decoration: underline; color: #1F3F83;'>" . $link . "</span>";
        }

        return $html . "</a>" . PHP_EOL;
    }
}
