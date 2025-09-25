<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Nette\Http\Url;
use Nette\InvalidArgumentException;
use Remp\MailerModule\Models\ContentGenerator\AllowedDomainManager;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

class AnchorRtmReplace implements IReplace
{
    use RtmReplaceTrait;

    private AllowedDomainManager $allowedDomainManager;

    public function __construct(AllowedDomainManager $allowedDomainManager)
    {
        $this->allowedDomainManager = $allowedDomainManager;
    }

    public function replace(string $content, GeneratorInput $generatorInput, array $context = null): string
    {
        $matches = [];
        preg_match_all('/<a(.*?)href="([^"]*?)"(.*?)>/is', $content, $matches);

        if (count($matches) > 0) {
            foreach ($matches[2] as $idx => $hrefUrl) {
                // parse URL
                try {
                    $url = new Url($hrefUrl);
                } catch (InvalidArgumentException $e) {
                    continue;
                }

                // check if the host is whitelisted
                if (!$this->allowedDomainManager->isAllowed($url->getHost())) {
                    continue;
                }

                $href = sprintf('<a%shref="%s"%s>', $matches[1][$idx], $this->replaceUrl($hrefUrl, $generatorInput), $matches[3][$idx]);
                $content = str_replace($matches[0][$idx], $href, $content);
            }
        }

        return $content;
    }
}
