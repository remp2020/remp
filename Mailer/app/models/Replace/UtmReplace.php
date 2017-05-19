<?php

namespace Remp\MailerModule\Replace;

class UtmReplace implements ReplaceInterface
{
    private $utmSource;

    private $utmMedium;

    private $utmCampaign;

    public function __construct($utmSource, $utmMedium, $utmCampaign)
    {
        $this->utmSource = $utmSource;
        $this->utmMedium = $utmMedium;
        $this->utmCampaign = $utmCampaign;
    }

    public function replace($content)
    {
        // specialny if kvoli ceskej urlke v 1 emaile ktora nefungovala s utm parametrami
        if (strpos($content, 'ihned.cz') !== false) {
            return $content;
        }

        $urlString = $this->formatUrlString();
        $content = preg_replace('/<a(.*)href="([^"#?]*)([^"]*)"(.*)>/i', '<a$1href="$2?' . $urlString . '$3"$4>', $content);

        // kedze nechceem mat 2x ? v urlke
        // tak treba ten druhy zrusit
        // v tom prvom regexpe som to nevedel tak napisat aby to tam dalo nedalo podla toho ci tam uz je
        // tak robim este jeden so strasnou magiou
        preg_match_all('/href="([^"]*)"/iU', $content, $matches);

        foreach ($matches[0] as $match) {
            $newHref = substr($match, 0, strpos($match, '?') + 1) . str_replace('?', '&', substr($match, strpos($match, '?') + 1));
            $content = str_replace($match, $newHref, $content);
        }

        return $content;
    }

    private function formatUrlString()
    {
        return "utm_source={$this->utmSource}&utm_medium={$this->utmMedium}&utm_campaign={$this->utmCampaign}";
    }
}
