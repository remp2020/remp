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
        $embed = Embed::create($url);
        $type = $embed->getType();
        $image = $embed->getImage();

        // twitter provider returns type `rich` for both image and video
        // so we have to check for type in og meta tags
        if ($embed->getProviderName() === "Twitter") {
            $type = $embed->getProviders()['opengraph']->getType();
        }

        return ($embed->getUrl() === null) ? null : [
            'link' => $embed->getUrl(),
            'title' => $embed->getTitle(),
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
        }

        return $html . "</a>" . PHP_EOL;
    }
}
