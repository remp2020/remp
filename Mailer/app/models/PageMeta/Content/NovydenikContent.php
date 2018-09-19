<?php

namespace Remp\MailerModule\PageMeta;

use Nette\Utils\Strings;

class NovydenikContent implements ContentInterface
{
    public function parseMeta($content)
    {
        // author
        $denniknAuthors = false;
        $matches = [];
        preg_match_all('/<cite class=\"d-author\"\>(.+)[\<]\/cite\>/U', $content, $matches);

        if ($matches) {
            foreach ($matches[1] as $author) {
                $denniknAuthors[] = Strings::upper(html_entity_decode($author));
            }
        }

        // title
        $title = false;
        $matches = [];
        preg_match('/<meta property=\"og:title\" content=\"(.+)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $title = html_entity_decode($matches[1]);
        }

        // description
        $description = false;
        $matches = [];
        preg_match('/<meta property=\"og:description\" content=\"(.*)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $description = html_entity_decode($matches[1]);
        }

        // image
        $image = false;
        $matches = [];
        preg_match('/<meta property=\"og:image\" content=\"(.+)\"\s*\/?/U', $content, $matches);
        if ($matches) {
            $image = $matches[1];

            $parts = explode('/', $image);
            $image = array_pop($parts);
            $info = pathinfo($image);
            if (preg_match('/-([0-9]+)x([0-9]+)$/i', $info['filename'])) {
                $newImageFileName = preg_replace('/-([0-9]+)x([0-9]+)$/', '-558x270', $info['filename']);
                $image = $newImageFileName . '.' . $info['extension'];
            } else {
                $image = $info['filename'] . '-558x270.' . $info['extension'];
            }
            $parts[] = $image;
            $image = implode('/', $parts);
        }

        return new Meta($title, $description, $image, $denniknAuthors);
    }
}
