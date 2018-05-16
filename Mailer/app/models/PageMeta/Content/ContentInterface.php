<?php

namespace Remp\MailerModule\PageMeta;

interface ContentInterface
{
    /** @return Meta */
    public function parseMeta($content);
}
