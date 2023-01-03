<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Remp\Journal\ListRequest;
use Remp\MailerModule\Models\Beam\JournalFactory;

class BeamConversionsRepository extends Repository implements IConversionsRepository
{
    private $journal;

    public function __construct(
        Explorer $database,
        JournalFactory $journalFactory,
        Storage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
        $this->journal = $journalFactory->getClient();
    }

    public function getBatchTemplatesConversionsSince(\DateTime $since): array
    {
        if (!$this->journal) {
            return [];
        }

        $purchases = [];

        // RTM + UTM fallback (to be removed)
        $request = (new ListRequest('commerce'))
            ->addSelect("step", "rtm_campaign", "rtm_content", "rtm_medium", "utm_campaign", "utm_content", "user_id", "token", "time")
            ->setTimeAfter($since)
            ->addFilter('step', 'purchase')
            ->addFilter('rtm_medium', 'email'); // condition translated to (RTM or UTM) on Segments API

        $result = $this->journal->list($request);
        foreach ($result as $record) {
            foreach ($record->commerces as $purchase) {
                $rtmContent = $purchase->source->rtm_content ?? $purchase->source->utm_content ?? null;
                $rtmCampaign = $purchase->source->rtm_campaign ?? $purchase->source->utm_campaign ?? null;

                if (empty($rtmContent)) {
                    // skip conversions without batch reference
                    continue;
                }
                if (empty($rtmCampaign)) {
                    // skip conversions without campaign (without reference to mail_template)
                    continue;
                }

                $purchases[$rtmContent][$rtmCampaign][$purchase->user->id] = $purchase->system->time;
            }
        }

        return $purchases;
    }

    public function getNonBatchTemplatesConversionsSince(\DateTime $since): array
    {
        if (!$this->journal) {
            return [];
        }

        $purchases = [];

        // RTM + UTM fallback (to be removed)
        $request = (new ListRequest('commerce'))
            ->addSelect("step", "rtm_campaign", "rtm_content", "rtm_medium", "utm_campaign", "utm_content", "user_id", "token", "time")
            ->setTimeAfter($since)
            ->addFilter('step', 'purchase')
            ->addFilter('rtm_medium', 'email'); // condition translated to (RTM or UTM) on Segments API

        $result = $this->journal->list($request);
        foreach ($result as $record) {
            foreach ($record->commerces as $purchase) {
                $rtmContent = $purchase->source->rtm_content ?? $purchase->source->utm_content ?? null;
                $rtmCampaign = $purchase->source->rtm_campaign ?? $purchase->source->utm_campaign ?? null;

                if (!empty($rtmContent)) {
                    // skip conversions with batch reference
                    continue;
                }
                if (empty($rtmCampaign)) {
                    // skip conversions without campaign (without reference to mail_template)
                    continue;
                }

                $purchases[$rtmCampaign][$purchase->user->id] = $purchase->system->time;
            }
        }

        return $purchases;
    }
}
