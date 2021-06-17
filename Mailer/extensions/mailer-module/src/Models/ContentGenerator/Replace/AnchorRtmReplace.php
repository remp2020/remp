<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Remp\MailerModule\Models\ContentGenerator\GeneratorInput;

class AnchorRtmReplace implements IReplace
{
    use RtmReplaceTrait;

    public function replace(string $content, GeneratorInput $generatorInput): string
    {
        $matches = [];
        preg_match_all('/<a(.*?)href="([^"]*?)"(.*?)>/i', $content, $matches);

        if (count($matches) > 0) {
            foreach ($matches[2] as $idx => $hrefUrl) {
                if (strpos($hrefUrl, 'http') === false) {
                    continue;
                }

                $href = sprintf('<a%shref="%s"%s>', $matches[1][$idx], $this->replaceUrl($hrefUrl, $generatorInput), $matches[3][$idx]);
                $content = str_replace($matches[0][$idx], $href, $content);
            }
        }

        return $content;
    }
}
