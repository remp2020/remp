<?php

namespace Remp\MailerModule\Generators;

use GuzzleHttp\Exception\RequestException;
use Remp\MailerModule\Api\v1\Handlers\Mailers\InvalidUrlException;
use Remp\MailerModule\PageMeta\ContentInterface;
use Remp\MailerModule\PageMeta\GuzzleTransport;
use Remp\MailerModule\PageMeta\PageMeta;
use Remp\MailerModule\PageMeta\TransportInterface;

class Utils
{
    public static function fetchUrlMeta($url, ContentInterface $content, TransportInterface $transportInterface = null)
    {
        if ($transportInterface == null) {
            $transportInterface = new GuzzleTransport();
        }

        $url = preg_replace('/\\?ref=(.*)/', '', $url);
        try {
            $pageMeta = new PageMeta($transportInterface, $content);
            $meta = $pageMeta->getPageMeta($url);
            if ($meta) {
                return $meta;
            }
            return false;
        } catch (RequestException $e) {
            throw new InvalidUrlException("Invalid URL: {$url}", 0, $e);
        }
    }
}
