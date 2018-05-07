<?php

namespace Remp\MailerModule\PageMeta;

class FileTransport implements TransportInterface
{
    public function getContent($url)
    {
        if (!is_file($url)) {
            return false;
        }
        return file_get_contents($url);
    }
}
