<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\PageMeta\Content;

use GuzzleHttp\Exception\RequestException;
use Remp\MailerModule\Models\PageMeta\Content\InvalidUrlException;
use Remp\MailerModule\Models\PageMeta\Content\JsonLDSchemaTrait;
use Remp\MailerModule\Models\PageMeta\Meta;
use Remp\MailerModule\Models\PageMeta\Transport\TransportInterface;

class DenniknShopContent implements ShopContentInterface
{
    use JsonLDSchemaTrait;
    use ShopSchemaTrait;

    public function __construct(private TransportInterface $transport)
    {
    }

    public function fetchUrlMeta(string $url): ?Meta
    {
        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $content = $this->transport->getContent($url);
            if ($content === null) {
                return null;
            }
            return $this->parseShopSchema($this->extractSchema($content));
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
    }
}
