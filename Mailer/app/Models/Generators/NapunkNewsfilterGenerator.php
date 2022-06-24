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
    public function process(array $values): array
    {
        $this->articleLocker->setLockText('Ezt a cikket csak a Napunk előfizetői olvashatják végig.');
        $this->articleLocker->setupLockLink('Csatlakozz hozzánk', 'https://predplatne.dennikn.sk/napunk-start');

        return parent::process($values);
    }
}
