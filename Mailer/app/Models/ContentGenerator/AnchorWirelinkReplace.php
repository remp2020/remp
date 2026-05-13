<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\ContentGenerator;

use Nette\Http\Url;
use Nette\InvalidArgumentException;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;
use Remp\MailerModule\Models\ContentGenerator\Replace\IReplace;
use Remp\MailerModule\Models\ContentGenerator\Replace\RtmClickReplace;

class AnchorWirelinkReplace implements IReplace
{
    public function __construct(
        private readonly string $wirelinkHost,
        private readonly array $wirelinkedDomains,
    ) {
    }

    public function replace(string $content, GeneratorInput $generatorInput, ?array $context = null): string
    {
        $matches = [];
        preg_match_all('/<a(\s[^>]*)href\s*=\s*([\"\']??)(http[^\"\'>]*?)\2([^>]*)>/iU', $content, $matches);

        if (count($matches[0]) > 0) {
            foreach ($matches[3] as $idx => $hrefUrl) {
                try {
                    $url = new Url($hrefUrl);
                } catch (InvalidArgumentException $e) {
                    continue;
                }

                if (!in_array($url->getHost(), $this->wirelinkedDomains, true)) {
                    continue;
                }

                $quote = $matches[2][$idx] ?: '"';

                $rtmClickHash = $url->getQueryParameter(RtmClickReplace::HASH_PARAM);
                if ($rtmClickHash) {
                    // we'll add rtm_click param to wrapper URL, no need to have it "inside"
                    $hrefUrl = RtmClickReplace::removeRtmClickHash($hrefUrl);
                }

                $wirelinkUrl = new Url($this->wirelinkHost);
                $wirelinkUrl->setPath(sprintf('/r/%s', rawurlencode($hrefUrl)));

                if ($rtmClickHash !== null) {
                    // set RTM click param to wrapped URL to make sure click tracking is able to extract it
                    $wirelinkUrl->setQueryParameter(RtmClickReplace::HASH_PARAM, $rtmClickHash);
                }

                $href = sprintf('<a%shref=%s%s%s%s>', $matches[1][$idx], $quote, $wirelinkUrl, $quote, $matches[4][$idx]);
                $content = str_replace($matches[0][$idx], $href, $content);
            }
        }

        return $content;
    }
}
