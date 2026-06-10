<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Nette\Http\Url;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Content\JsonLDContent;
use Remp\MailerModule\Models\PageMeta\Meta;

class DenniknContent extends JsonLDContent
{
    use ShopSchemaTrait;

    public function fetchUrlMeta(string $url): ?Meta
    {
        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $content = $this->transport->getContent($url);
            if ($content === null) {
                return null;
            }

            if (strpos($url, 'obchod.dennikn.sk') !== false) {
                return $this->parseShopSchema($this->extractSchema($content));
            }

            return $this->parseMeta($content);
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
    }

    protected function postProcessMeta(Meta $meta): Meta
    {
        return new Meta(
            $meta->getTitle(),
            $meta->getDescription(),
            $this->processImage($meta->getImage()),
            $meta->getAuthors(),
        );
    }

    private function processImage(?string $imageUrl): string
    {
        if (!$imageUrl) {
            return 'https://static.novydenik.com/2018/11/placeholder_2@2x.png';
        }

        if (!str_starts_with($imageUrl, 'https://img.projektn.sk')) {
            return $imageUrl;
        }

        $url = new Url($imageUrl);
        $url = (string) $url->appendQuery(['w' => 558, 'h' => 270, 'fit' =>'crop']);

        // return placeholder if image doesn't exist
        $response = get_headers($url);
        if (strpos($response[0], '404')) {
            return 'https://img.projektn.sk/wp-static/2018/10/placeholder_2@2x.png';
        }

        return $url;
    }
}
