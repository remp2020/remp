<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Nette\Http\Url;
use Nette\InvalidArgumentException;
use Remp\MailerModule\Models\ContentGenerator\AllowedDomainManager;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

/**
 * UrlRtmReplace replaces (adds) RTM (REMP UTM) parameters if content contains only URL and nothing else.
 * This is handy if you need to work with RTM parameters in your email params and not just the content itself.
 */
class UrlRtmReplace implements IReplace
{
    use RtmReplaceTrait;

    private AllowedDomainManager $allowedDomainManager;

    public function __construct(AllowedDomainManager $allowedDomainManager)
    {
        $this->allowedDomainManager = $allowedDomainManager;
    }

    public function replace(string $content, GeneratorInput $generatorInput, array $context = null): string
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
        if (!$this->allowedDomainManager->isAllowed($url->getHost())) {
            return $content;
        }

        return $this->replaceUrl($content, $generatorInput);
    }
}
