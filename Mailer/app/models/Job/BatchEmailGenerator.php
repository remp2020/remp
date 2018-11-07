<?php

namespace Remp\MailerModule\Job;

use Psr\Log\LoggerAwareTrait;
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

    use LoggerAwareTrait;

    private $mailJobsRepository;

    private $mailJobQueueRepository;

    private $batchesRepository;

    private $segmentAggregator;

    private $userProvider;

    private $mailCache;

    private $templates = [];

    private $unreadArticlesGenerator;

    public function __construct(
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
    }

    public function generate(ActiveRow $batch)
    {
        $this->mailJobQueueRepository->clearBatch($batch);

        $batchInsert = 1000;
        $insert = [];
        $processed = 0;

        $job = $batch->job;

        $userMap = [];
        $userIds = $this->segmentAggregator->users(['provider' => $batch->mail_job->segment_provider, 'code' => $batch->mail_job->segment_code]);

        foreach (array_chunk($userIds, 1000, true) as $userIdsChunk) {
            $page = 1;
            while ($users = $this->userProvider->list($userIdsChunk, $page)) {
                foreach ($users as $user) {
                    $userMap[$user['email']] = $user['id'];
                    $templateId = $this->getTemplate($batch);

                    $insert[] = [
                        'batch' => $batch,
                        'templateId' => $templateId,
                        'email' => $user['email'],
                        'sorting' => rand(),
                        'context' => $job->context,
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

        $queueJobs = $this->mailJobQueueRepository->getBatchEmails($batch, 0, null);
        $this->mailCache->pauseQueue($batch->id);

        $userJobOptions = [];

        /** @var ActiveRow $queueJob */
        foreach ($queueJobs as $queueJob) {
            $template = $queueJob->ref('mail_templates', 'mail_template_id');
            $userId = $userMap[$queueJob->email];
            $jobOptions = [
                'email' => $queueJob->email,
                'code' => $template->code,
                'mail_batch_id' => $queueJob->mail_batch_id,
                'context' => $queueJob->context,
                'parameters' => []
            ];

            // Load dynamic parameters
            if ($template->extras) {
                $extras = json_decode($template->extras, true);
                $generator = @$extras['generator'];

                if ($generator === self::BEAM_UNREAD_ARTICLES_GENERATOR) {
                    $jobOptions['generator'] = $generator;
                    $parameters = $extras['parameters'] ?? false;
                    if ($parameters) {
                        $this->unreadArticlesGenerator->addToResolve($template->code, $userId, $parameters);
                    }
                } elseif ($generator !== null) {
                    $this->logger->log(LogLevel::ERROR, sprintf('Generating dynamic emails: unknown generator: %s', $generator));
                }
            }
            $userJobOptions[$userId] = $jobOptions;
        }

        // Resolve all dynamic parameters at once
        $this->unreadArticlesGenerator->resolve();

        foreach ($userJobOptions as $userId => $jobOptions) {
            if ($jobOptions['generator'] === self::BEAM_UNREAD_ARTICLES_GENERATOR) {
                $jobOptions['params'] = $this->unreadArticlesGenerator->getMailParameters($jobOptions['code'], $userId);
            }

            $this->mailCache->addJob(
                $userId,
                $jobOptions['email'],
                $jobOptions['code'],
                $jobOptions['mail_batch_id'],
                $jobOptions['context'],
                $jobOptions['params']
            );
        }

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
