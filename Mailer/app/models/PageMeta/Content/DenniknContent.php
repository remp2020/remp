<?php

namespace Remp\MailerModule\PageMeta;

use GuzzleHttp\Exception\RequestException;
use Nette\Http\Url;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use Remp\MailerModule\Api\v1\Handlers\Mailers\InvalidUrlException;

class DenniknContent implements ContentInterface
{
    private $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function fetchUrlMeta($url): ?Meta
    {
        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $content = $this->transport->getContent($url);
            if (!$content) {
                return null;
            }
            $meta = $this->parseMeta($content);
            if (!$meta) {
                return null;
            }
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
        return $meta;
    }

    public function parseMeta($content)
    {
        preg_match_all('/<script id="schema" type="application\/ld\+json">(.*?)<\/script>/', $content, $matches);

        if (!$matches) {
            return new Meta(false, false, false, false);
        }

        try {
            $schema = Json::decode($matches[1][0]);
        } catch (JsonException $e) {
            return new Meta(false, false, false, false);
        }

        // author
        $denniknAuthors = false;
        if (isset($schema->author) && !is_array($schema->author)) {
            $schema->author = [$schema->author];
        }
        foreach ($schema->author as $author) {
            $denniknAuthors[] = Strings::upper($author->name);
        }

        $title = $schema->headline ?? false;
        $description = $schema->description ?? false;
        $image = $this->processImage($schema->image->url ?? null);

        return new Meta($title, $description, $image, $denniknAuthors);
    }

    private function processImage($imageUrl)
    {
        if (!$imageUrl) {
            return 'https://static.novydenik.com/2018/11/placeholder_2@2x.png';
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
