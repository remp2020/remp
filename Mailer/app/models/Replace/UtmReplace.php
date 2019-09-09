<?php

namespace Remp\MailerModule\Replace;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

class UtmReplace implements ReplaceInterface
{
    private $utmSource; // newsfilter

    private $utmMedium; // email

    private $utmCampaign; // campaign/email identifier

    private $utmContent; // specific job identifier

    public function __construct($utmSource, $utmMedium, $utmCampaign, $utmContent)
    {
        $this->utmSource = $utmSource;
        $this->utmMedium = $utmMedium;
        $this->utmCampaign = $utmCampaign;
        $this->utmContent = $utmContent;
    }

    public function replace($content)
    {
        // avoid html entities on url
        $content = html_entity_decode($content);
        // prepare UTM parameters to insert into url
        $urlString = $this->formatUrlString();
        // extract query string from url
        $query = preg_replace('/<a(.*?)href="([^"#?]*)([^"]*?)"(.*?)>/i', '$2$3', $content);
        // remove undesired utm parameter from others systems
        $query = $this->removeUtms($query);
        // rebuild url with all above
        $content = preg_replace('/<a(.*?)href="([^"#?]*)([^"]*?)"(.*?)>/i', '<a$1href="$2?' . $urlString . $query .'"$4>', $content);

        // make sure we don't have two "?" characters in query string
        preg_match_all('/href="([^"]*)"/iU', $content, $matches);

        foreach ($matches[0] as $match) {
            $newHref = substr($match, 0, strpos($match, '?') + 1) . str_replace('?', '&', substr($match, strpos($match, '?') + 1));
            $content = str_replace($match, $newHref, $content);
        }

        return $content;
    }

    private function formatUrlString()
    {
        return "utm_source={$this->utmSource}&utm_medium={$this->utmMedium}&utm_campaign={$this->utmCampaign}&utm_content={$this->utmContent}";
    }

    private function removeUtms($url) {

        $url_array = parse_url($url);
        $query_array = [];
        if(!empty($url_array['query'])){
            $url_array['query'] = html_entity_decode($url_array['query']);
            parse_str($url_array['query'], $query_array);
            unset($query_array['utm_source']);
            unset($query_array['utm_medium']);
            unset($query_array['utm_campaign']);
            unset($query_array['utm_content']);
        }

        return (count($query_array) > 0 ? '&':'').http_build_query($query_array);

    }

}
