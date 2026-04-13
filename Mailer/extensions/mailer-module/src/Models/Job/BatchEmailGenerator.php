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
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;

class BatchEmailGenerator
{
    const BEAM_UNREAD_ARTICLES_RESOLVER = 'beam-unread-articles';

    private int $deleteLimit = 10000;

    private $templates = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly JobQueueRepository $mailJobQueueRepository,
        private readonly Aggregator $segmentAggregator,
        private readonly IUser $userProvider,
        private readonly MailCache $mailCache,
        private readonly UnreadArticlesResolver $unreadArticlesResolver,
        private readonly UserSubscriptionsRepository $userSubscriptionsRepository,
        private readonly BatchTemplatesRepository $batchTemplatesRepository,
    ) {
    }

    public function setDeleteLimit($limit): void
    {
        $this->deleteLimit = $limit;
    }

    private function computeUserIds(ActiveRow $batch): array
    {
        $usersIds = [];

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
            $usersIds = array_unique(array_merge($usersIds, $includeUserIds), SORT_NUMERIC);
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
            $usersIds = array_diff($usersIds, $excludeUserIds);
        }
        return $usersIds;
    }

    protected function insertUsersIntoJobQueue(ActiveRow $batch, &$userEmailIdMap): array
    {
        $this->logger->info('Clearing batch', ['batchId' => $batch->id]);
        $this->mailJobQueueRepository->clearBatch($batch);

        $templateUsersCount = [];

        $job = $batch->job;
        $batchTemplate = $this->batchTemplatesRepository->findByBatchId($batch->id)->fetch();
        if (!$batchTemplate) {
            throw new \RuntimeException("Unable to find mail batch template for batch [$batch->id]");
        }
        $isExternal = $batchTemplate->mail_template->mail_type->is_external;

        $batchInserter = new MailJobQueueBatchInserter($this->mailJobQueueRepository);

        if ($isExternal) {
            $this->logger->info('Processing users for external mail type to mail_job_queue', ['batchId' => $batch->id]);
            $mailTypeId = $batchTemplate->mail_template->mail_type_id;
            $subscribersQuery = $this->userSubscriptionsRepository->getTable()
                ->where('mail_type_id', $mailTypeId)
                ->where('subscribed', true)
                ->order('id ASC');

            // $lastId is faster than offset pagination
            $lastId = 0;
            $batchSize = 1000;
            while (true) {
                $subscribers = (clone $subscribersQuery)
                    ->where('id > ?', $lastId)
                    ->limit($batchSize)
                    ->fetchAll();

                if (empty($subscribers)) {
                    break;
                }

                foreach ($subscribers as $subscriber) {
                    $lastId = $subscriber->id;
                    $templateId = $this->getTemplate($batch);
                    $templateUsersCount[$templateId] = ($templateUsersCount[$templateId] ?? 0) + 1;
                    $batchInserter->add([
                        'batch' => $batch->id,
                        'templateId' => $templateId,
                        'email' => $subscriber->user_email,
                        'sorting' => rand(), /** @phpstan-ignore-line */
                        'context' => $job->context,
                        'params' => json_encode([]), // we do not have other user data
                    ]);
                }
            }
        } else {
            $this->logger->info('Processing users from segments to mail_job_queue', ['batchId' => $batch->id]);
            $userIds = $this->computeUserIds($batch);
            foreach (array_chunk($userIds, 1000, true) as $userIdsChunk) {
                $page = 1;
                while ($users = $this->userProvider->list($userIdsChunk, $page)) {
                    foreach ($users as $user) {
                        $userEmailIdMap[$user['email']] = $user['id'];
                        $templateId = $this->getTemplate($batch);
                        $templateUsersCount[$templateId] = ($templateUsersCount[$templateId] ?? 0) + 1;
                        $batchInserter->add([
                            'batch' => $batch->id,
                            'templateId' => $templateId,
                            'email' => $user['email'],
                            'sorting' => rand(), /** @phpstan-ignore-line */
                            'context' => $job->context,
                            'params' => json_encode($user), // forward all user attributes to template params
                        ]);
                    }
                    $page++;
                }
            }
        }
        $batchInserter->flush();

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
        $this->logger->info('Acquiring users for batch', [
            'batchId' => $batch->id,
        ]);

        $userEmailIdMap = []; // not all emails have ID mapped on them
        $templateUsersCount = $this->insertUsersIntoJobQueue($batch, $userEmailIdMap);
        foreach ($templateUsersCount as $templateId => $count) {
            $this->logger->info('Generating batch queue', [
                'batchId' => $batch->id,
                'templateId' => $templateId,
                'usersCount' => $count,
            ]);
        }

        $templateUsersCount = $this->filterQueue($batch);
        foreach ($templateUsersCount as $templateId => $count) {
            $this->logger->info('Users from batch queue filtered', [
                'batchId' => $batch->id,
                'templateId' => $templateId,
                'usersCount' => $count,
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
                if (!$template) {
                    throw new \RuntimeException("Unable to find mail_template referenced by queue job with ID [$queueJob->id]");
                }
                $userId = $userEmailIdMap[$queueJob->email] ?? null;
                $jobOptions = [
                    'user_id' => $userId,
                    'email' => $queueJob->email,
                    'code' => $template->code,
                    'mail_batch_id' => $queueJob->mail_batch_id,
                    'context' => $queueJob->context,
                    'params' => json_decode($queueJob->params, true) ?? [],
                ];

                // Retrieve dynamic parameters (specified by 'extras')
                if ($userId && $template->extras) {
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
                $userJobOptions[] = $jobOptions;
                $lastId = $queueJob->id;
            }

            // Resolve dynamic parameters for given jobs at once
            $this->unreadArticlesResolver->resolve();

            foreach ($userJobOptions as $jobOptions) {
                $userId = $jobOptions['user_id'] ?? null;
                if ($userId && ($jobOptions['handler'] ?? null) === self::BEAM_UNREAD_ARTICLES_RESOLVER) {
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
                    email: $jobOptions['email'],
                    templateCode: $jobOptions['code'],
                    queueId: $jobOptions['mail_batch_id'],
                    userId: $userId,
                    context: $jobOptions['context'],
                    params: $jobOptions['params'],
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
