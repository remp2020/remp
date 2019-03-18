<?php

namespace Remp\MailerModule\Generators;

use Embed\Embed;

class EmbedParser
{
    private function fetch($url)
    {
        $embed = Embed::create($url);
        $type = $embed->getType();
        $image = $embed->getImage();

        // twitter provider returns type `rich` for both image and video
        // so we have to check for type in og meta tags
        if ($embed->getProviderName() === "Twitter") {
            $type = $embed->getProviders()['opengraph']->getType();
        }

        return ($embed->getUrl() === null) ? null : [
            'link' => $embed->getUrl(),
            'title' => $embed->getTitle(),
            'text' => $embed->getDescription(),
            'image' => $image,
        ];
    }

    public function parse($link)
    {
        if (!isset($link[0]) && empty($link[0])) {
            return null;
        }

        $link = trim($link[0]);

        // facebook
        if (preg_match('/^(?:(?:https?:)?\/\/)?(?:www\.)?facebook\.com\/[a-zA-Z0-9\.]+\/videos\/(?:[a-zA-Z0-9\.]+\/)?([0-9]+)/', $link)
            || preg_match('/youtu/', $link)
            || preg_match('/twitt/', $link)
        ) {
            if ($data = $this->fetch($link)) {
                return $this->createEmbeddMarkup($data['link'], $data['title'], $data['text'], $data['image']);
            }
        }

        return null;
    }

    public function createEmbeddMarkup($link, $title, $text = null, $image = null)
    {
        $html = "<br><a href='{$link}' target='_blank' style='display: block;text-align: center;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-decoration:none;'>";

        if (!is_null($image)) {
            $html .= "
                <img src='{$image}' 
                     alt='{$title}' 
                     style='
                        outline:none;
                        text-decoration:none;
                        -ms-interpolation-mode:bicubic;
                        width:auto;
                        max-width:100%;
                        clear:both;
                        display:inline;
                     '>
            ";
        }

        return $html . "</a>" . PHP_EOL;
    }
}
