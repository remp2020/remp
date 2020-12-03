<?php
declare(strict_types=1);

namespace Remp\MailerModule\PageMeta;

class FileTransport implements TransportInterface
{
    public function getContent(string $url): ?string
    {
        if (!is_file($url)) {
            return null;
        }
        return file_get_contents($url);
    }
}
