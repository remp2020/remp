<?php

namespace Remp\MailerModule\PageMeta;

use Nette\Utils\Strings;

class DenniknContent implements ContentInterface
{
    public function parseMeta($content)
    {
        // author
        $denniknAuthors = false;
        $matches = [];
        preg_match_all('/<cite class=\"d-author\"\>(.+)[\<]\/cite\>/U', $content, $matches);

        if ($matches) {
            foreach ($matches[1] as $author) {
                $denniknAuthors[] = Strings::upper($author);
            }
        }

        // title
        $title = false;
        $matches = [];
        // nastavil som to na og:title - nie su tam vyescapovane veci
        // preg_match('/<h2 class=\"d-title\"\>(.+)[\<]\/h2\>/U', $content, $matches);
        preg_match('/<meta property=\"og:title\" content=\"(.+)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $title = $matches[1];
        }

        // description
        $description = false;
        $matches = [];
        preg_match('/<meta property=\"og:description\" content=\"(.+)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $description = $matches[1];
        }

        // image
        $image = false;
        $matches = [];
        preg_match('/<meta property=\"og:image\" content=\"(.+)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $image = $matches[1];

            // vygenerujeme skaredo urlky na mensi obrazok
            $parts = explode('/', $image);
            $image = array_pop($parts);
            $info = pathinfo($image);
            if (preg_match('/-([0-9]+)x([0-9]+)$/i', $info['filename'])) {
                $newImageFileName = preg_replace('/-([0-9]+)x([0-9]+)$/', '-200x120', $info['filename']);
                $image = $newImageFileName . '.' . $info['extension'];
            } else {
                $image = $info['filename'] . '-200x120.' . $info['extension'];
            }
            $parts[] = $image;
            $image = implode('/', $parts);
        }

        return new Meta($title, $description, $image, $denniknAuthors);
    }
}
