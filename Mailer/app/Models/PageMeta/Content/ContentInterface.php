<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\PageMeta\Content;

use Remp\MailerModule\Models\PageMeta\Meta;

interface ContentInterface
{
    public function fetchUrlMeta(string $url): ?Meta;
}
