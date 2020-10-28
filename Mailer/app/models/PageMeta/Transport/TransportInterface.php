<?php
declare(strict_types=1);

namespace Remp\MailerModule\PageMeta;

interface TransportInterface
{
    public function getContent(string $url): ?string;
}
