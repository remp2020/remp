<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use Remp\MailerModule\Models\PageMeta\Content\ShopContentInterface;
use Remp\MailerModule\Models\PageMeta\Meta;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;

class NovydenikShopContent implements ShopContentInterface
{
    private $transport;

    public function __construct(TransportInterface $transport)
    {
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
            $meta = $this->parseMeta($content);
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
}
