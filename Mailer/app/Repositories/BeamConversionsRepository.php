<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Remp\Journal\ListRequest;
use Remp\MailerModule\Models\Beam\JournalFactory;

class BeamConversionsRepository extends Repository implements IConversionsRepository
{
    private $journal;

    public function __construct(
        Context $database,
        JournalFactory $journalFactory,
        IStorage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
        $this->journal = $journalFactory->getClient();
    }

    public function getBatchTemplatesConversions(array $batchIds, array $mailTemplateCodes): array
    {
        if (!$this->journal) {
            return [];
        }

        $purchases = [];

        // RTM
        $request = (new ListRequest('commerce'))
            ->addSelect("step", "rtm_campaign", "rtm_content", "user_id", "token", "time")
            ->addFilter('step', 'purchase')
            ->addFilter('rtm_campaign', ...$mailTemplateCodes)
            ->addFilter('rtm_content', ...$batchIds)
            ->addGroup('rtm_campaign', 'rtm_content');

        $result = $this->journal->list($request);
        foreach ($result as $record) {
            if (empty($record->tags->rtm_content)) {
                // skip conversions without batch reference
                continue;
            }
            if (empty($record->tags->rtm_campaign)) {
                // skip conversions without campaign (without reference to mail_template)
                continue;
            }
            foreach ($record->commerces as $purchase) {
                $purchases[$record->tags->rtm_content][$record->tags->rtm_campaign][$purchase->user->id] = $purchase->system->time;
            }
        }
        // TODO add UTM fallback

        return $purchases;
    }

    public function getNonBatchTemplateConversions(array $mailTemplateCodes): array
    {
        $purchases = [];

        // RTM
        $request = (new ListRequest('commerce'))
            ->addSelect("step", "rtm_campaign", "rtm_content", "user_id", "token", "time")
            ->addFilter('step', 'purchase')
            ->addFilter('rtm_campaign', ...$mailTemplateCodes)
            ->addGroup('rtm_campaign');

        $result = $this->journal->list($request);

        foreach ($result as $record) {
            if (!empty($record->tags->rtm_content) && is_numeric($record->tags->rtm_content)) {
                // skip all batch-related conversions, but keep conversions referencing other type of campaigns (e.g. banner)
                continue;
            }
            if (empty($record->tags->rtm_campaign)) {
                // skip conversions without campaign (without reference to mail_template)
                continue;
            }
            foreach ($record->commerces as $purchase) {
                $purchases[$record->tags->rtm_campaign][$purchase->user->id] = $purchase->system->time;
            }
        }


        // UTM fallback -- to be removed
        $request = (new ListRequest('commerce'))
            ->addSelect("step", "utm_campaign", "utm_content", "user_id", "token", "time")
            ->addFilter('step', 'purchase')
            ->addFilter('utm_campaign', ...$mailTemplateCodes)
            ->addGroup('utm_campaign');

        $result = $this->journal->list($request);

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
