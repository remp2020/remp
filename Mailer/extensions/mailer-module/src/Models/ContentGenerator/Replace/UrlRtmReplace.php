<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Nette\Http\Url;
use Nette\InvalidArgumentException;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

/**
 * UrlRtmReplace replaces (adds) RTM (REMP UTM) parameters if content contains only URL and nothing else.
 * This is handy if you need to work with RTM parameters in your email params and not just the content itself.
 */
class UrlRtmReplace implements IReplace
{
    use RtmReplaceTrait;

    private $hostWhitelist = [];

    public function addHost(string $host)
    {
        $this->hostWhitelist[$host] = true;
    }

    public function replace(string $content, GeneratorInput $generatorInput): string
    {
        // fast check to avoid unnecessary parsing
        if (strpos($content, 'http') !== 0) {
            return $content;
        }

        // parse URL
        try {
            $url = new Url($content);
        } catch (InvalidArgumentException $e) {
            return $content;
        }

        // check if the host is whitelisted
        if (!isset($this->hostWhitelist[$url->getHost()])) {
            return $content;
        }

        return $this->replaceUrl($content, $generatorInput);
    }
}
