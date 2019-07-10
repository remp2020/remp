<?php

namespace Remp\MailerModule\Job;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Remp\MailerModule\ActiveRow;
use Remp\MailerModule\Generators\Dynamic\UnreadArticlesGenerator;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\JobQueueRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Segment\Aggregator;
use Remp\MailerModule\User\IUser;

class BatchEmailGenerator
{
    const BEAM_UNREAD_ARTICLES_GENERATOR = 'beam-unread-articles';

    private $mailJobsRepository;

    private $mailJobQueueRepository;

    private $batchesRepository;

    private $segmentAggregator;

    private $userProvider;

    private $mailCache;

    private $templates = [];

    private $unreadArticlesGenerator;

    private $logger;

    public function __construct(
        LoggerInterface $logger,
        JobsRepository $mailJobsRepository,
        JobQueueRepository $mailJobQueueRepository,
        BatchesRepository $batchesRepository,
        Aggregator $segmentAggregator,
        IUser $userProvider,
        MailCache $mailCache,
        UnreadArticlesGenerator $unreadArticlesGenerator
    ) {
        $this->mailJobsRepository = $mailJobsRepository;
        $this->mailJobQueueRepository = $mailJobQueueRepository;
        $this->batchesRepository = $batchesRepository;
        $this->segmentAggregator = $segmentAggregator;
        $this->userProvider = $userProvider;
        $this->mailCache = $mailCache;
        $this->unreadArticlesGenerator = $unreadArticlesGenerator;
        $this->logger = $logger;
    }

    protected function insertUsersIntoJobQueue(ActiveRow $batch, &$userMap): array
    {
        $this->mailJobQueueRepository->clearBatch($batch);

        $batchInsert = 1000;
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
                        'sorting' => rand(),
                        'context' => $job->context,
                        'params' => json_encode($user) // forward all user attributes to template params
                    ];
                    ++$processed;
                    if ($processed == $batchInsert) {
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

        $userJobOptions = [];
        for ($i = 0; $i <= ceil($totalQueueSize / $limit); $i++) {
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

                // Load dynamic parameters
                if ($template->extras) {
                    $extras = json_decode($template->extras, true);
                    $generator = $extras['generator'] ?? null;

                    if ($generator === self::BEAM_UNREAD_ARTICLES_GENERATOR) {
                        $jobOptions['generator'] = $generator;
                        $parameters = $extras['parameters'] ?? false;
                        if ($parameters) {
                            $this->unreadArticlesGenerator->addToResolve($template->code, $userId, $parameters);
                        }
                    } elseif ($generator !== null) {
                        $this->logger->log(LogLevel::ERROR, "Generating dynamic emails: unknown generator: {$generator}");
                    }
                }
                $userJobOptions[$userId] = $jobOptions;
                $lastId = $queueJob->id;
            }
        }

        // Resolve all dynamic parameters at once
        $this->unreadArticlesGenerator->resolve();

        $regularJobsCount = $generatorJobsCount = 0;

        foreach ($userJobOptions as $userId => $jobOptions) {
            $generatorJob = false;
            if ($jobOptions['generator'] ?? null === self::BEAM_UNREAD_ARTICLES_GENERATOR) {
                $additionalParams = $this->unreadArticlesGenerator->getMailParameters($jobOptions['code'], $userId);
                // Generator params override user params
                foreach ($additionalParams as $name => $value) {
                    $jobOptions['params'][$name] = $value;
                }
                $generatorJob = true;
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
                if ($generatorJob) {
                    $generatorJobsCount++;
                } else {
                    $regularJobsCount++;
                }
            }
        }
        $this->logger->info("Jobs inserted into mail cache", [
            'regularJobsCount' => $regularJobsCount,
            'generatorJobsCount' => $generatorJobsCount
        ]);

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
