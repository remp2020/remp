<?php

namespace Remp\MailerModule\PageMeta;

interface TransportInterface
{
    public function getContent($url);
}
