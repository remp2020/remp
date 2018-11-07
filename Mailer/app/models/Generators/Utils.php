<?php

namespace Remp\MailerModule\Generators;

use GuzzleHttp\Exception\RequestException;
use Remp\MailerModule\Api\v1\Handlers\Mailers\InvalidUrlException;
use Remp\MailerModule\PageMeta\ContentInterface;
use Remp\MailerModule\PageMeta\Meta;
use Remp\MailerModule\PageMeta\PageMeta;
use Remp\MailerModule\PageMeta\TransportInterface;

class Utils
{
    public static function fetchUrlMeta($url, ContentInterface $content, TransportInterface $transport): Meta
    {
        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $pageMeta = new PageMeta($transport, $content);
            return $pageMeta->getPageMeta($url);
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
    }
}
