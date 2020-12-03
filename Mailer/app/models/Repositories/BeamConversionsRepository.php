<?php
declare(strict_types=1);

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

    public function getBatchTemplatesConversions(array $batchIds, array $mailTemplateCodes): array
    {
        if (!$this->journal) {
            return [];
        }

        $request = (new ListRequest('commerce'))
            ->addSelect("step", "utm_campaign", "utm_content", "user_id", "token", "time")
            ->addFilter('step', 'purchase')
            ->addFilter('utm_campaign', ...$mailTemplateCodes)
            ->addFilter('utm_content', ...$batchIds)
            ->addGroup('utm_campaign', 'utm_content');

        $result = $this->journal->list($request);

        $purchases = [];
        foreach ($result as $record) {
            if (empty($record->tags->utm_content)) {
                // skip conversions without batch reference
                continue;
            }
            if (empty($record->tags->utm_campaign)) {
                // skip conversions without campaign (without reference to mail_template)
                continue;
            }
            foreach ($record->commerces as $purchase) {
                $purchases[$record->tags->utm_content][$record->tags->utm_campaign][$purchase->user->id] = $purchase->system->time;
            }
        }

        return $purchases;
    }

    public function getNonBatchTemplateConversions(array $mailTemplateCodes): array
    {
        $request = (new ListRequest('commerce'))
            ->addSelect("step", "utm_campaign", "utm_content", "user_id", "token", "time")
            ->addFilter('step', 'purchase')
            ->addFilter('utm_campaign', ...$mailTemplateCodes)
            ->addGroup('utm_campaign');

        $result = $this->journal->list($request);

        $purchases = [];
        foreach ($result as $record) {
            if (!empty($record->tags->utm_content) && is_numeric($record->tags->utm_content)) {
                // skip all batch-related conversions, but keep conversions referencing other type of campaigns (e.g. banner)
                continue;
            }
            if (empty($record->tags->utm_campaign)) {
                // skip conversions without campaign (without reference to mail_template)
                continue;
            }
            foreach ($record->commerces as $purchase) {
                $purchases[$record->tags->utm_campaign][$purchase->user->id] = $purchase->system->time;
            }
        }

        return $purchases;
    }
}
