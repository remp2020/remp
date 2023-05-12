<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Nette\Http\Url;
use Nette\InvalidArgumentException;
use Remp\MailerModule\Models\ContentGenerator\AllowedDomainManager;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

class TextUrlRtmReplace implements IReplace
{
    use RtmReplaceTrait;

    private AllowedDomainManager $allowedDomainManager;

    public function __construct(AllowedDomainManager $allowedDomainManager)
    {
        $this->allowedDomainManager = $allowedDomainManager;
    }

    public function replace(string $content, GeneratorInput $generatorInput, array $context = null): string
    {
        if ($content !== strip_tags($content)) {
            // This replacer is intended to be used only for text emails. HTML is handled by AnchorRtmReplace.
            return $content;
        }

        $matches = [];
        preg_match_all('/(https?:\/\/.+?)(\s)/i', $content, $matches);

        if (count($matches) > 0) {
            foreach ($matches[1] as $idx => $hrefUrl) {
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

                $finalUrl = $this->replaceUrl($hrefUrl, $generatorInput) . $matches[2][$idx];
                $content = str_replace($matches[0][$idx], $finalUrl, $content);
            }
        }

        return $content;
    }
}
