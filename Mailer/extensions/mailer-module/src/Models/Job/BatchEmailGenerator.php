<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Job;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Remp\MailerModule\Models\Beam\UnreadArticlesResolver;
use Remp\MailerModule\Models\Beam\UserUnreadArticlesResolveException;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Models\Users\IUser;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\JobQueueRepository;

class BatchEmailGenerator
{
    const BEAM_UNREAD_ARTICLES_RESOLVER = 'beam-unread-articles';

    private int $deleteLimit = 10000;

    private $templates = [];

    public function __construct(
        private LoggerInterface $logger,
        private JobQueueRepository $mailJobQueueRepository,
        private Aggregator $segmentAggregator,
        private IUser $userProvider,
        private MailCache $mailCache,
        private UnreadArticlesResolver $unreadArticlesResolver,
    ) {
    }

    public function setDeleteLimit($limit): void
    {
        $this->deleteLimit = $limit;
    }

    protected function insertUsersIntoJobQueue(ActiveRow $batch, &$userMap): array
    {
        $this->logger->info('Clearing batch', ['batchId' => $batch->id]);
        $this->mailJobQueueRepository->clearBatch($batch);

        $batchInsert = 200;
        $insert = [];
        $processed = 0;

        $templateUsersCount = [];

        $job = $batch->job;

        $usersSegments = [];

        $jobSegmentsManager = new JobSegmentsManager($batch->mail_job);

        $this->logger->info('Fetching users from include segments', ['batchId' => $batch->id]);
        $includeSegments = $jobSegmentsManager->getIncludeSegments();
        foreach ($includeSegments as $segment) {
            $this->logger->info('Fetching users from the include segment', [
                'batchId' => $batch->id,
                'provider' => $segment['provider'],
                'code' => $segment['code'],
            ]);

            $includeUserIds = $this->segmentAggregator->users(['provider' => $segment['provider'], 'code' => $segment['code']]);
            $usersSegments = array_unique(array_merge($usersSegments, $includeUserIds), SORT_NUMERIC);
        }

        $this->logger->info('Fetching users from exclude segments', ['batchId' => $batch->id]);
        $excludeSegments = $jobSegmentsManager->getExcludeSegments();
        foreach ($excludeSegments as $segment) {
            $this->logger->info('Fetching users from the exclude segment', [
                'batchId' => $batch->id,
                'provider' => $segment['provider'],
                'code' => $segment['code'],
            ]);

            $excludeUserIds = $this->segmentAggregator->users(['provider' => $segment['provider'], 'code' => $segment['code']]);
            $usersSegments = array_diff($usersSegments, $excludeUserIds);
        }

        $this->logger->info('Processing users from segments to mail_job_queue', ['batchId' => $batch->id]);
        foreach (array_chunk($usersSegments, 1000, true) as $userIdsChunk) {
            $page = 1;
            while ($users = $this->userProvider->list($userIdsChunk, $page)) {
                foreach ($users as $user) {
                    $userMap[$user['email']] = $user['id'];
                    $templateId = $this->getTemplate($batch);

                    $templateUsersCount[$templateId] = ($templateUsersCount[$templateId] ?? 0) + 1;

                    $insert[] = [
                        'batch' => $batch,
                        'templateId' => $templateId,
                        'email' => $user['email'],
                        'sorting' => rand(), /** @phpstan-ignore-line */
                        'context' => $job->context,
                        'params' => json_encode($user) // forward all user attributes to template params
                    ];
                    ++$processed;
                    if ($processed === $batchInsert) {
                        $processed = 0;
                        $this->mailJobQueueRepository->multiInsert($insert);
                        $insert = [];
                    }
                }
                $page++;
            }
        }

        if ($processed) {
            $this->mailJobQueueRepository->multiInsert($insert);
        }

        return $templateUsersCount;
    }

    protected function filterQueue($batch): array
    {
        $job = $batch->job;
        $this->logger->info('Users in queue before filter: ' . $this->mailJobQueueRepository->getBatchUsersCount($batch), ['batchId' => $batch->id]);

        $this->logger->info('Removing unsubscribed', ['batchId' => $batch->id]);
        $this->mailJobQueueRepository->removeUnsubscribed($batch, $this->deleteLimit);
        $this->logger->info('Users left in queue: ' . $this->mailJobQueueRepository->getBatchUsersCount($batch), ['batchId' => $batch->id]);
        if ($job->mail_type_variant_id) {
            $this->logger->info('Removing other variants', ['batchId' => $batch->id]);
            $this->mailJobQueueRepository->removeOtherVariants($batch, $job->mail_type_variant_id, $this->deleteLimit);
            $this->logger->info('Users left in queue: ' . $this->mailJobQueueRepository->getBatchUsersCount($batch), ['batchId' => $batch->id]);
        }
        if ($job->context) {
            $this->logger->info('Removing already sent context', ['batchId' => $batch->id]);
            $this->mailJobQueueRepository->removeAlreadySentContext($batch, $job->context, $this->deleteLimit);
            $this->logger->info('Users left in queue: ' . $this->mailJobQueueRepository->getBatchUsersCount($batch), ['batchId' => $batch->id]);
        }
        $this->logger->info('Removing already queued in other job batch', ['batchId' => $batch->id]);
        $this->mailJobQueueRepository->removeAlreadyQueued($batch, $this->deleteLimit);
        $this->logger->info('Users left in queue: ' . $this->mailJobQueueRepository->getBatchUsersCount($batch), ['batchId' => $batch->id]);
        $this->logger->info('Removing already sent template', ['batchId' => $batch->id]);
        $this->mailJobQueueRepository->removeAlreadySent($batch, $this->deleteLimit);
        $this->logger->info('Users left in queue: ' . $this->mailJobQueueRepository->getBatchUsersCount($batch), ['batchId' => $batch->id]);
        if ($batch->max_emails) {
            $this->logger->info('Removing emails above configured count', ['batchId' => $batch->id]);
            $this->mailJobQueueRepository->stripEmails($batch, $batch->max_emails, $this->deleteLimit);
            $this->logger->info('Users left in queue: ' . $this->mailJobQueueRepository->getBatchUsersCount($batch), ['batchId' => $batch->id]);
        }

        // Count remaining users
        $templateUsersCount = [];
        $q = $this->mailJobQueueRepository->getTable()
                ->select('count(*) AS users_count, mail_template_id')
                ->where(['mail_batch_id' => $batch->id])
                ->group('mail_template_id');

        foreach ($q->fetchAll() as $row) {
            $templateUsersCount[$row->mail_template_id] = $row->users_count;
        }
        return $templateUsersCount;
    }

    public function generate(ActiveRow $batch)
    {
        $userMap = [];
        $this->logger->info('Acquiring users for batch', [
            'batchId' => $batch->id,
        ]);

        $templateUsersCount = $this->insertUsersIntoJobQueue($batch, $userMap);
        foreach ($templateUsersCount as $templateId => $count) {
            $this->logger->info('Generating batch queue', [
                'batchId' => $batch->id,
                'templateId' => $templateId,
                'usersCount' => $count
            ]);
        }

        $templateUsersCount = $this->filterQueue($batch);
        foreach ($templateUsersCount as $templateId => $count) {
            $this->logger->info('Users from batch queue filtered', [
                'batchId' => $batch->id,
                'templateId' => $templateId,
                'usersCount' => $count
            ]);
        }

        $queueJobsSelection = $this->mailJobQueueRepository->getBatchEmails($batch, 0, null);
        $this->mailCache->pauseQueue($batch->id);

        $totalQueueSize = (clone $queueJobsSelection)->count('*');
        $lastId = PHP_INT_MIN;
        $limit = 1000;
        $jobsCount = 0;

        for ($i = 0, $iMax = ceil($totalQueueSize / $limit); $i <= $iMax; $i++) {
            $userJobOptions = [];
            $queueJobs = (clone $queueJobsSelection)
                ->where('id > ?', $lastId)
                ->limit($limit);

            /** @var ActiveRow $queueJob */
            foreach ($queueJobs as $queueJob) {
                $template = $queueJob->ref('mail_templates', 'mail_template_id');
                $userId = $userMap[$queueJob->email];
                $jobOptions = [
                    'email' => $queueJob->email,
                    'code' => $template->code,
                    'mail_batch_id' => $queueJob->mail_batch_id,
                    'context' => $queueJob->context,
                    'params' => json_decode($queueJob->params, true) ?? []
                ];

                // Retrieve dynamic parameters (specified by 'extras')
                if ($template->extras) {
                    $extras = json_decode($template->extras, true);
                    $extrasHandler = $extras['handler'] ?? null;

                    // Unread articles are resolved for multiple users at once, add them to resolver queue
                    if ($extrasHandler === self::BEAM_UNREAD_ARTICLES_RESOLVER) {
                        $jobOptions['handler'] = $extrasHandler;
                        $parameters = $extras['parameters'] ?? false;
                        if ($parameters) {
                            $this->unreadArticlesResolver->addToResolveQueue($template->code, $userId, $parameters);
                        }
                    } elseif ($extrasHandler !== null) {
                        $this->logger->log(LogLevel::ERROR, "Unknown extras handler: {$extrasHandler}");
                    }
                }
                $userJobOptions[$userId] = $jobOptions;
                $lastId = $queueJob->id;
            }

            // Resolve dynamic parameters for given jobs at once
            $this->unreadArticlesResolver->resolve();

            foreach ($userJobOptions as $userId => $jobOptions) {
                if ($jobOptions['handler'] ?? null === self::BEAM_UNREAD_ARTICLES_RESOLVER) {
                    try {
                        $additionalParams = $this->unreadArticlesResolver->getResolvedMailParameters($jobOptions['code'], $userId);
                        foreach ($additionalParams as $name => $value) {
                            $jobOptions['params'][$name] = $value;
                        }
                    } catch (UserUnreadArticlesResolveException $exception) {
                        // just log and continue to next user
                        $this->logger->log(LogLevel::ERROR, $exception->getMessage());
                        continue;
                    }
                }

                $result = $this->mailCache->addJob(
                    $userId,
                    $jobOptions['email'],
                    $jobOptions['code'],
                    $jobOptions['mail_batch_id'],
                    $jobOptions['context'],
                    $jobOptions['params']
                );

                if ($result !== false) {
                    $jobsCount++;
                }
            }
        }

        $this->logger->info('Jobs inserted into mail cache', ['jobsCount' => $jobsCount]);
    }

    private function getTemplate(ActiveRow $batch)
    {
        if (isset($this->templates[$batch->id])) {
            return $this->templates[$batch->id][ array_rand($this->templates[$batch->id]) ];
        }

        $this->templates[$batch->id] = [];

        $templates = $batch->related('mail_job_batch_templates');
        /** @var ActiveRow $template */
        foreach ($templates as $template) {
            $this->templates[$batch->id] = array_merge($this->templates[$batch->id], array_fill(0, $template->weight, $template->mail_template_id));
        }

        return $this->templates[$batch->id][ array_rand($this->templates[$batch->id]) ];
    }
}
