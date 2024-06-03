<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Generators;

use Embed\Embed;
use Embed\Http\Crawler;
use Embed\Http\CurlClient;
use Tracy\Debugger;

class EmbedParser
{
    protected ?string $videoLinkText;

    protected array $curlSettings = [];

    public function setVideoLinkText(?string $videoLinkText = null): void
    {
        $this->videoLinkText = $videoLinkText;
    }

    public function setCurlSettings(array $settings)
    {
        $this->curlSettings = $settings;
    }

    private function fetch(string $url): ?array
    {
        $curlSettings = [
            // Twitter may generate infinite redirect (2024/03),
            // fix according to https://github.com/oscarotero/Embed/issues/520#issue-1782756560
            'follow_location' => !$this->isTwitterLink($url),
            ...$this->curlSettings,
        ];

        $curlClient = new CurlClient();
        $curlClient->setSettings($curlSettings);

        $embed = new Embed(new Crawler($curlClient));
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
            'isVideo' => $type === 'video',
        ];
    }

    private function isTwitterLink($link)
    {
        return str_contains($link, 'twitt') || str_contains($link, 'x.com');
    }

    public function parse(string $link): ?string
    {
        $link = trim($link);

        if (preg_match('/^(?:(?:https?:)?\/\/)?(?:www\.)?facebook\.com\/[a-zA-Z0-9.]+\/videos\/(?:[a-zA-Z0-9.]+\/)?([0-9]+)/', $link)
            || str_contains($link, 'youtu')
            || $this->isTwitterLink($link)
        ) {
            try {
                if ($data = $this->fetch($link)) {
                    return $this->createEmbedMarkup($data['link'], $data['title'], $data['image'], $data['isVideo']);
                }
            } catch (\Embed\Http\NetworkException $exception) {
                Debugger::log("Network error while retrieving embedded link [$link], reason: " . $exception->getMessage(), Debugger::EXCEPTION);
                return $this->createEmbedMarkup($link);
            }
        }

        return null;
    }

    public function createEmbedMarkup(string $link, ?string $title = null, ?string $image = null, bool $isVideo = false): string
    {
        $html = "<br>";

        if ($isVideo && isset($this->videoLinkText)) {
            $html .= "<p style='text-align: center; font-weight: normal;'><i>{$this->videoLinkText}</i></p><br>";
        }

        $html .= "<a href='{$link}' target='_blank' style='color:#181818;padding:0;margin:0;line-height:1.3;text-decoration:none;text-align: center; display: block;'>";

        if (!is_null($image) && !is_null($title)) {
            $html .= "<img src='{$image}' alt='{$title}' style='outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:inline;'>";
        } else {
            $html .= "<span style='text-decoration: underline; color: #1F3F83;'>" . $link . "</span>";
        }

        return $html . "</a>" . PHP_EOL;
    }
}
