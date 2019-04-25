<?php

namespace Remp\MailerModule\PageMeta;

use GuzzleHttp\Exception\RequestException;
use Nette\Http\Url;
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
        // author
        $denniknAuthors = false;
        $matches = [];
        preg_match_all('/<cite class=\"d-author\"\>(.+)[\<]\/cite\>/U', $content, $matches);

        if ($matches) {
            foreach ($matches[1] as $author) {
                $denniknAuthors[] = Strings::upper(html_entity_decode($author));
            }
        }

        // title
        $title = false;
        $matches = [];
        preg_match('/<meta property=\"og:title\" content=\"(.+)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $title = html_entity_decode($matches[1]);
        }

        // description
        $description = false;
        $matches = [];
        preg_match('/<meta property=\"og:description\" content=\"(.*)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $description = html_entity_decode($matches[1]);
        }

        // image
        $image = false;
        $matches = [];
        preg_match('/<meta property=\"og:image\" content=\"(.+)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $images = $this->processImage($matches[1]);
            $image = $images['main'];
        }

        return new Meta($title, $description, $image, $denniknAuthors);
    }

    private function processImage($imageUrl)
    {
        $url = new Url($imageUrl);
        if ($url->getHost() !== 'img.projektn.sk') {
            $url = new Url('https://img.projektn.sk/wp-static' . $url->path);
        }

        $images = [
            'small' => (string) $url->appendQuery(['w' => 350, 'h' => 220, 'fit' =>'crop']),
            'main' => (string) $url->appendQuery(['w' => 558, 'h' => 270, 'fit' =>'crop']),
        ];

        // return placeholder if image doesn't exist
        $response = get_headers($images['small']);
        if (strpos($response[0], '404')) {
            $images['small'] = 'https://img.projektn.sk/wp-static/2018/10/placeholder_1@2x.png';
        }
        $response = get_headers($images['main']);
        if (strpos($response[0], '404')) {
            $images['main'] = 'https://img.projektn.sk/wp-static/2018/10/placeholder_2@2x.png';
        }

        return $images;
    }
}
