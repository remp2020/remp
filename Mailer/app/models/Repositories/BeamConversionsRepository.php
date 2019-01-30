<?php

namespace Remp\MailerModule\Repository;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Remp\Journal\ListRequest;
use Remp\MailerModule\Beam\JournalFactory;
use Remp\MailerModule\Repository;
use Remp\MailerModule\User\IUser;

class BeamConversionsRepository extends Repository implements IConversionsRepository
{
    private $journal;

    private $userBase;

    public function __construct(
        Context $database,
        JournalFactory $journalFactory,
        IUser $userBase,
        IStorage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
        $this->journal = $journalFactory->getClient();
        $this->userBase = $userBase;
    }

    public function getBatchTemplateConversions($batchId, $mailTemplateCode): array
    {
        $request = (new ListRequest('commerce'))
            ->addFilter('step', 'purchase')
            ->addFilter('utm_content', $batchId)
            ->addFilter('utm_campaign', $mailTemplateCode)
            ->addGroup('utm_content');

        $result = $this->journal->list($request);

        $purchases = [];
        foreach ($result as $record) {
            if (empty($record->tags)) {
                continue;
            }
            foreach ($record->commerces as $purchase) {
                $purchases[$purchase->user->id] = $purchase->system->time;
            }
        }

        return $purchases;
    }

    public function getNonBatchTemplateConversions($mailTemplateCode): array
    {
        $request = (new ListRequest('commerce'))
            ->addSelect("step", "utm_campaign", "utm_content", "user_id", "token", "time")
            ->addFilter('step', 'purchase')
            ->addFilter('utm_campaign', $mailTemplateCode)
            ->addGroup('utm_content');

        $result = $this->journal->list($request);

        $purchases = [];
        foreach ($result as $record) {
            if (!empty($record->tags)) {
                continue;
            }
            foreach ($record->commerces as $purchase) {
                $purchases[$purchase->user->id] = $purchase->system->time;
            }
        }

        return $purchases;
    }
}
