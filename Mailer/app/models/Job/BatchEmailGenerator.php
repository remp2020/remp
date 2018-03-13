<?php

namespace Remp\MailerModule\Job;

use Remp\MailerModule\ActiveRow;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\JobQueueRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Segment\Aggregator;
use Remp\MailerModule\User\IUser;

class BatchEmailGenerator
{
    private $mailJobsRepository;

    private $mailJobQueueRepository;

    private $batchesRepository;

    private $segmentAggregator;

    private $userProvider;

    private $mailCache;

    private $templates = [];

    public function __construct(
        JobsRepository $mailJobsRepository,
        JobQueueRepository $mailJobQueueRepository,
        BatchesRepository $batchesRepository,
        Aggregator $segmentAggregator,
        IUser $userProvider,
        MailCache $mailCache
    ) {
        $this->mailJobsRepository = $mailJobsRepository;
        $this->mailJobQueueRepository = $mailJobQueueRepository;
        $this->batchesRepository = $batchesRepository;
        $this->segmentAggregator = $segmentAggregator;
        $this->userProvider = $userProvider;
        $this->mailCache = $mailCache;
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

        /** @var ActiveRow $queueJob */
        foreach ($queueJobs as $queueJob) {
            /** @var ActiveRow $template */
            $template = $queueJob->ref('mail_templates', 'mail_template_id');
            $this->mailCache->addJob($userMap[$queueJob->email], $queueJob->email, $template->code, $queueJob->mail_batch_id, $queueJob->context);
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
