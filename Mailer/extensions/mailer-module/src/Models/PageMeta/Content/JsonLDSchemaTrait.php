<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta\Content;

use Nette\Utils\Json;
use Nette\Utils\JsonException;

trait JsonLDSchemaTrait
{
    /**
     * Extracts and JSON-decodes the n-th JSON+LD (`application/ld+json`) script block from the
     * given HTML. Returns null when the block is missing or contains invalid JSON.
     */
    protected function extractSchema(string $content): ?\stdClass
    {
        preg_match_all('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $content, $matches);
        if (empty($matches[1][0])) {
            return null;
        }
        try {
            return Json::decode($matches[1][0]);
        } catch (JsonException $e) {
            return null;
        }
    }
}
