<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators\Euobserver;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Nette\Utils\Json;
use Remp\Mailer\Models\Generators\EmbedParser as DefaultEmbedParser;
use Tracy\Debugger;

class EmbedParser extends DefaultEmbedParser
{
    public function createEmbedMarkup(string $link, ?string $title = null, ?string $image = null, bool $isVideo = false): string
    {
        if ($this->isTwitterLink($link)) {
            $html = '';

            if ($imageUrl = $this->fetchXPreview($link)) {
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
            $html .= "</p>";

            return $html;
        }

        return parent::createEmbedMarkup($link, $title, $image, $isVideo);
    }

    private function fetchXPreview(string $url): ?string
    {
        if (!preg_match('/status\/(\d+)/', $url, $matches)) {
            return null;
        }
        $xId = $matches[1];

        $client = new Client();
        try {
            $response = $client->get("https://cdn.syndication.twimg.com/tweet-result?id={$xId}&token=!");
        } catch (ClientException $e) {
            $response = $e->getResponse()->getBody();
            Debugger::log('Unable to fetch X embed: ' . $response, Debugger::EXCEPTION);
            return null;
        } catch (ServerException $e) {
            Debugger::log($e, Debugger::EXCEPTION);
            return null;
        }

        $data = Json::decode($response->getBody()->getContents(), forceArrays: true);

        if (!empty($data['mediaDetails'])) {
            return $data['mediaDetails'][0]['media_url_https'] ?? null;
        }

        return null;
    }
}
