<?php

namespace Remp\MailerModule\Models\ContentGenerator\Replace;

use Nette\Caching\Storage;
use Nette\Http\Url;
use Remp\MailerModule\Models\Config\Config;
use Remp\MailerModule\Repositories\MailTemplateLinksRepository;

abstract class RtmClickReplace implements IReplace
{
    public const HASH_PARAM = 'rtm_click';
    public const CONFIG_NAME = 'mail_click_tracker';

    public function __construct(
        protected MailTemplateLinksRepository $mailTemplateLinksRepository,
        protected Config $config,
        protected Storage $storage
    ) {
    }

    protected function isEnabled(): bool
    {
        return $this->config->get(self::CONFIG_NAME);
    }

    protected function computeUrlHash(Url $url, string $additionalInfo = ''): string
    {
        return hash('crc32c', $url->setQuery([])->getAbsoluteUrl() . $additionalInfo);
    }
}
