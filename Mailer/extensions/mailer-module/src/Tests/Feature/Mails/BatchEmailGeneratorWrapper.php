<?php
declare(strict_types=1);

namespace Tests\Feature\Mails;

use Psr\Log\NullLogger;
use Remp\MailerModule\Models\Beam\UnreadArticlesResolver;
use Remp\MailerModule\Models\Job\BatchEmailGenerator;
use Remp\MailerModule\Models\Job\MailCache;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Models\Users\IUser;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\JobsRepository;

class BatchEmailGeneratorWrapper extends BatchEmailGenerator
{
    public function __construct(
        JobQueueRepository $mailJobQueueRepository,
        Aggregator $segmentAggregator,
        IUser $userProvider,
        MailCache $mailCache,
        UnreadArticlesResolver $unreadArticlesGenerator,
    ) {
        parent::__construct(
            new NullLogger(),
            $mailJobQueueRepository,
            $segmentAggregator,
            $userProvider,
            $mailCache,
            $unreadArticlesGenerator,
        );
    }

    // To enable public access to protected functions in tests
    public function insertUsersIntoJobQueue(ActiveRow $batch, &$userMap): array
    {
        return parent::insertUsersIntoJobQueue($batch, $userMap);
    }

    public function filterQueue($batch): array
    {
        return parent::filterQueue($batch);
    }
}
