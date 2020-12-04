<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta\Transport;

interface TransportInterface
{
    public function getContent(string $url): ?string;
}
