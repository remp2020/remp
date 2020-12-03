<?php
declare(strict_types=1);

namespace Tests\Feature\Mails;

use Psr\Log\NullLogger;
use Remp\MailerModule\ActiveRow;
use Remp\MailerModule\Beam\UnreadArticlesResolver;
use Remp\MailerModule\Job\BatchEmailGenerator;
use Remp\MailerModule\Job\MailCache;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\JobQueueRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Segment\Aggregator;
use Remp\MailerModule\User\IUser;

class BatchEmailGeneratorWrapper extends BatchEmailGenerator
{
    public function __construct(
        JobsRepository $mailJobsRepository,
        JobQueueRepository $mailJobQueueRepository,
        BatchesRepository $batchesRepository,
        Aggregator $segmentAggregator,
        IUser $userProvider,
        MailCache $mailCache,
        UnreadArticlesResolver $unreadArticlesGenerator
    ) {
        parent::__construct(
            new NullLogger(),
            $mailJobsRepository,
            $mailJobQueueRepository,
            $batchesRepository,
            $segmentAggregator,
            $userProvider,
            $mailCache,
            $unreadArticlesGenerator
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
