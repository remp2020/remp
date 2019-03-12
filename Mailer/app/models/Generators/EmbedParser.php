<?php

namespace Remp\MailerModule\Generators;

use Embed\Embed;

class EmbedParser
{
    private function fetch($url)
    {
        $data = Embed::create($url);
        $og = $data->getProviders()['opengraph'];
        $image = !empty($og->getImagesUrls()) ? $og->getImagesUrls()[0] : null;

        return ($og->getUrl() === null) ? null : [
            'link' => $og->getUrl(),
            'title' => $og->getTitle(),
            'text' => $og->getDescription(),
            'image' => $this->getBase64EncodedImage($image, $og->getType() === 'video'),
        ];
    }

    private function getBase64EncodedImage($imageLink, $video = false)
    {
        $image = imagecreatefromstring(file_get_contents($imageLink));

        if ($video) {
            $play = imagecreatefrompng(__DIR__ . '/../../../www/assets/img/play.png');

            imagecopy(
                $image,
                $play,
                (imagesx($image)/2) - (imagesx($play)/2),
                (imagesy($image)/2) - (imagesy($play)/2),
                0,
                0,
                imagesx($play),
                imagesy($play)
            );
        }

        ob_start();
        imagejpeg($image);
        $image_data = ob_get_contents();
        ob_end_clean();

        return base64_encode($image_data);
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
            $data = $this->fetch($link);

            if ($data === null) {
                return '<span style="color: red;">Tento link už nie je dostupný.</span>';
            }

            return $this->createEmbeddMarkup($data['link'], $data['title'], $data['text'], $data['image']);
        }

        return null;
    }

    public function createEmbeddMarkup($link, $title, $text = null, $image = null)
    {
        $html = "<br><a href='{$link}' target='_blank' style='color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-decoration:none;'>";

        if (!is_null($image)) {
            $html .= "
                <img src='data:image/png;base64, {$image}' 
                     alt='{$title}' 
                     style='
                        outline:none;
                        text-decoration:none;
                        -ms-interpolation-mode:bicubic;
                        width:auto;
                        max-width:100%;
                        clear:both;
                        display:block;
                        margin-bottom:20px;
                     '>
            ";
        }

        return $html .= "</a></br></br>" . PHP_EOL;
    }
}
