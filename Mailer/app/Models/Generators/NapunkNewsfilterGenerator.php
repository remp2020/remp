<?php

namespace Remp\Mailer\Models\Generators;

use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\ArticleLocker;
use Remp\MailerModule\Models\Generators\EmbedParser;
use Remp\MailerModule\Models\Generators\WordpressHelpers;
use Remp\MailerModule\Models\PageMeta\Content\ContentInterface;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;

class NapunkNewsfilterGenerator extends NewsfilterGenerator
{
    public function __construct(
        SourceTemplatesRepository $mailSourceTemplateRepository,
        WordpressHelpers $helpers,
        ContentInterface $content,
        EmbedParser $embedParser,
        ArticleLocker $articleLocker,
        EngineFactory $engineFactory
    ) {
        $articleLocker->setLockText('Ezt a cikket csak a Napunk előfizetői olvashatják végig.');
        $articleLocker->setupLockLink('Csatlakozz hozzánk', 'https://predplatne.dennikn.sk/napunk-start');

        parent::__construct(
            $mailSourceTemplateRepository,
            $helpers,
            $content,
            $embedParser,
            $articleLocker,
            $engineFactory
        );
    }
}
