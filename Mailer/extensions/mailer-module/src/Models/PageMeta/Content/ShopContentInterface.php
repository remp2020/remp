<?php

namespace Remp\MailerModule\Models\PageMeta\Content;

use Remp\MailerModule\Models\PageMeta\Meta;

interface ShopContentInterface
{
    public function fetchUrlMeta(string $url): ?Meta;
}
