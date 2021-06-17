<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Job;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Models\Beam\UserUnreadArticlesResolveException;
use Remp\MailerModule\Models\Beam\UnreadArticlesResolver;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Models\Segment\Aggregator;
use Remp\MailerModule\Models\Users\IUser;

class BatchEmailGenerator
{
    const BEAM_UNREAD_ARTICLES_RESOLVER = 'beam-unread-articles';

    private $mailJobsRepository;

    private $mailJobQueueRepository;

    private $batchesRepository;

    private $segmentAggregator;

    private $userProvider;

    private $mailCache;

    private $templates = [];

    private $unreadArticlesResolver;

    private $logger;

    public function __construct(
        LoggerInterface $logger,
        JobsRepository $mailJobsRepository,
        JobQueueRepository $mailJobQueueRepository,
        BatchesRepository $batchesRepository,
        Aggregator $segmentAggregator,
        IUser $userProvider,
        MailCache $mailCache,
        UnreadArticlesResolver $unreadArticlesResolver
    ) {
        $this->mailJobsRepository = $mailJobsRepository;
        $this->mailJobQueueRepository = $mailJobQueueRepository;
        $this->batchesRepository = $batchesRepository;
        $this->segmentAggregator = $segmentAggregator;
        $this->userProvider = $userProvider;
        $this->mailCache = $mailCache;
        $this->unreadArticlesResolver = $unreadArticlesResolver;
        $this->logger = $logger;
    }

    protected function insertUsersIntoJobQueue(ActiveRow $batch, &$userMap): array
    {
        $this->mailJobQueueRepository->clearBatch($batch);

        $batchInsert = 200;
        $insert = [];
        $processed = 0;

        $templateUsersCount = [];

        $job = $batch->job;

        $userIds = $this->segmentAggregator->users(['provider' => $batch->mail_job->segment_provider, 'code' => $batch->mail_job->segment_code]);

        foreach (array_chunk($userIds, 1000, true) as $userIdsChunk) {
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
                        'sorting' => mt_rand(),
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

        $this->mailJobQueueRepository->removeUnsubscribed($batch);
        if ($job->mail_type_variant_id) {
            $this->mailJobQueueRepository->removeOtherVariants($batch, $job->mail_type_variant_id);
        }
        if ($job->context) {
            $this->mailJobQueueRepository->removeAlreadySentContext($batch, $job->context);
        }
        $this->mailJobQueueRepository->removeAlreadyQueued($batch);
        $this->mailJobQueueRepository->removeAlreadySent($batch);
        $this->mailJobQueueRepository->stripEmails($batch, $batch->max_emails);

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

        $priority = $this->batchesRepository->getBatchPriority($batch);
        $this->mailCache->restartQueue($batch->id, $priority);
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
