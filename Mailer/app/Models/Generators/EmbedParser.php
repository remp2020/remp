<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RedirectMiddleware;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tracy\Debugger;

class EmbedParser extends \Remp\MailerModule\Models\Generators\EmbedParser
{
    private const IMAGE_CONNECT_TIMEOUT = 3;
    private const IMAGE_TIMEOUT = 5;
    private const X_PREVIEW_CONNECT_TIMEOUT = 3;
    private const X_PREVIEW_TIMEOUT = 5;

    protected string $twitterLinkText = "Click to display on X (Twitter)";

    protected ?string $embedImagePreprocessingUrl = null;

    protected ?string $salt = '';

    public function setTwitterLinkText(?string $twitterLinkText = null): void
    {
        $this->twitterLinkText = $twitterLinkText;
    }

    public function setEmbedImagePreprocessingUrl(string $embedImagePreprocessingUrl, string $salt): void
    {
        $this->embedImagePreprocessingUrl = $embedImagePreprocessingUrl;
        $this->salt = $salt;
    }

    protected function isTwitterLink($link): bool
    {
        return str_contains($link, 'twitt') || str_contains($link, 'x.com');
    }

    public function createEmbedMarkup(string $link, ?string $title = null, ?string $image = null, bool $isVideo = false): string
    {
        $html = "<br>";

        $html .= "<a href='{$link}' target='_blank' style='color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-decoration:none;text-align: center; display: block;width:100%;'>";

        if (!is_null($image) && !is_null($title)) {
            if ($this->embedImagePreprocessingUrl) {
                $preprocessedImage = sprintf($this->embedImagePreprocessingUrl, $image, hash('sha256', $this->salt . $image));
                $image = $this->resolveImage($preprocessedImage) ?? $image;
            }
            $html .= "<img src='{$image}' alt='{$title}' style='outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;max-width:100%;clear:both;display:inline;width:100%;height:auto;'>";
        } elseif ($this->isTwitterLink($link)) {
            return "<br><a style=\"display: block;margin: 0 0 20px;padding: 7px 10px;text-decoration: none;text-align: center;font-weight: bold;font-family:'Helvetica Neue', Helvetica, Arial;color: #249fdc; background: #ffffff; border: 3px solid #249fdc;margin: 16px 0 16px 0\" href=\"{$link}\" target=\"_blank\">{$this->twitterLinkText}</a>";
        } else {
            $html .= "<span style='text-decoration: underline; color: #1F3F83;'>" . $link . "</span>";
        }

        if ($isVideo && isset($this->videoLinkText)) {
            $html .= "<p style='color: #888;font-family: Arial,sans-serif;font-size: 14px;margin: 0; padding: 0;margin-top:5px;line-height: 1.3;text-align: left; text-decoration: none;'><i>{$this->videoLinkText}</i></p><br>";
        }

        return $html . "</a>" . PHP_EOL;
    }

    protected function resolveImage(string $link): ?string
    {
        $client = new Client();
        try {
            $response = $client->head($link, [
                'connect_timeout' => self::IMAGE_CONNECT_TIMEOUT,
                'timeout' => self::IMAGE_TIMEOUT,
                'allow_redirects' => ['track_redirects' => true], // in case there's redirect to canonical
            ]);
        } catch (TransferException $e) {
            return null;
        }

        $redirects = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);

        return end($redirects) ?: $link;
    }

    protected function fetchXPreviewImage(string $link): ?string
    {
        if (!preg_match('/status\/(\d+)/', $link, $matches)) {
            return null;
        }
        $xId = $matches[1];

        $client = new Client();
        try {
            $response = $client->get("https://cdn.syndication.twimg.com/tweet-result?id={$xId}&token=!", [
                'connect_timeout' => self::X_PREVIEW_CONNECT_TIMEOUT,
                'timeout' => self::X_PREVIEW_TIMEOUT,
            ]);
            $data = Json::decode($response->getBody()->getContents(), forceArrays: true);
        } catch (ClientException $e) {
            Debugger::log('Unable to fetch X embed: ' . (string) $e->getResponse()->getBody(), Debugger::EXCEPTION);
            return null;
        } catch (TransferException | JsonException $e) {
            Debugger::log($e, Debugger::EXCEPTION);
            return null;
        }

        if (!empty($data['mediaDetails'])) {
            return $data['mediaDetails'][0]['media_url_https'] ?? null;
        }

        return null;
    }
}
