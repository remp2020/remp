<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Nette\Http\Url;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Meta;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;

class NovydenikContent implements ContentInterface
{
    private $transport;

    public function __construct(
        TransportInterface $transport
    ) {
        $this->transport = $transport;
    }

    public function fetchUrlMeta(string $url): ?Meta
    {
        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $content = $this->transport->getContent($url);
            if ($content === null) {
                return null;
            }
            if (strpos($url, 'obchod.denikn.cz') !== false) {
                $meta = $this->parseShopMeta($content);
            } else {
                $meta = $this->parseMeta($content);
            }
            if (!$meta) {
                return null;
            }
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
        return $meta;
    }

    public function parseMeta(string $content): ?Meta
    {
        preg_match_all('/<script id="schema" type="application\/ld\+json">(.*?)<\/script>/', $content, $matches);

        if (!$matches || empty($matches[1])) {
            return null;
        }

        try {
            $schema = Json::decode($matches[1][0]);
        } catch (JsonException $e) {
            return null;
        }

        // author
        $denniknAuthors = [];
        if (isset($schema->author) && !is_array($schema->author)) {
            $schema->author = [$schema->author];
        }
        foreach ($schema->author as $author) {
            $denniknAuthors[] = Strings::upper($author->name);
        }

        $title = $schema->headline ?? null;
        $description = $schema->description ?? null;
        $image = $this->processImage($schema->image->url ?? null);

        return new Meta($title, $description, $image, $denniknAuthors);
    }

    public function parseShopMeta(string $content): ?Meta
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $content, $matches);

        if (!$matches || empty($matches[1])) {
            return null;
        }

        try {
            $schema = Json::decode($matches[1][1]);
        } catch (JsonException $e) {
            return null;
        }

        // authors
        $authors = [];
        if (isset($schema->dataFeedElement[0]->author)) {
            $authors = explode(',', $schema->dataFeedElement[0]->author->name);
        }

        // title
        $title = $schema->dataFeedElement[0]->name ?? null;

        // description
        $description = null;
        if (isset($schema->dataFeedElement[0]->workExample[0]->abstract)) {
            $description = str_replace('\n', '', Strings::truncate($schema->dataFeedElement[0]->workExample[0]->abstract, 200));
        }

        // image
        $image = $schema->dataFeedElement[0]->workExample[0]->image ?? null;

        return new Meta($title, $description, $image, $authors);
    }

    private function processImage(?string $imageUrl): string
    {
        if (!$imageUrl) {
            return 'https://static.novydenik.com/2018/11/placeholder_2@2x.png';
        }

        $url = new Url($imageUrl);
        $url = (string) $url->appendQuery(['w' => 558, 'h' => 270, 'fit' =>'crop']);

        // return placeholder if image doesn't exist
        $response = get_headers($url);
        if (strpos($response[0], '404')) {
            return 'https://static.novydenik.com/2018/11/placeholder_2@2x.png';
        }

        return $url;
    }
}
