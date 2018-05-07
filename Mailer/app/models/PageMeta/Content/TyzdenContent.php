<?php

namespace Remp\MailerModule\PageMeta;

class  TyzdenContent implements ContentInterface
{
    public function parseMeta($content)
    {
        // author
        $authors = false;
        $matches = [];
        preg_match_all('/<meta name=\"author\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            foreach ($matches[1] as $author) {
                $authors[] = $author;
            }
        }

        // title
        $title = false;
        $matches = [];
        preg_match('/<meta property=\"og:title\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            $title = $matches[1];
        }

        // description
        $description = false;
        $matches = [];
        preg_match('/<meta property=\"og:description\" content=\"(.+)\">/Us', $content, $matches);
        if ($matches) {
            $description = $matches[1];
        }

        // image
        $image = false;
        $matches = [];
        preg_match('/<meta property=\"og:image\" content=\"(.+)\">/U', $content, $matches);
        if ($matches) {
            $image = $matches[1];
        }

        return new Meta($title, $description, $image, $authors);
    }
}
