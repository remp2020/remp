<?php
declare(strict_types=1);

namespace Remp\MailerModule\PageMeta;

interface ContentInterface
{
    public function fetchUrlMeta(string $url): ?Meta;
}
