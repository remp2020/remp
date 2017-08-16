<?php

namespace Remp\MailerModule\Job;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository\JobQueueRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Segment\Aggregator;

class BatchEmailGenerator
{
    private $mailJobsRepository;

    private $mailJobQueueRepository;

    private $segmentAggregator;

    private $mailCache;

    private $templates = [];

    public function __construct(
        JobsRepository $mailJobsRepository,
        JobQueueRepository $mailJobQueueRepository,
        Aggregator $segmentAggregator,
        MailCache $mailCache
    ) {
        $this->mailJobsRepository = $mailJobsRepository;
        $this->mailJobQueueRepository = $mailJobQueueRepository;
        $this->segmentAggregator = $segmentAggregator;
        $this->mailCache = $mailCache;
    }

    public function generate(IRow $batch)
    {
        $this->mailJobQueueRepository->clearBatch($batch);

        $batchInsert = 1000;
        $insert = [];
        $processed = 0;

        $job = $batch->job;

        $users = $this->segmentAggregator->users(['provider' => $batch->mail_job->segment_provider, 'code' => $batch->mail_job->segment_code]);

        foreach ($users as $user) {
            $templateId = $this->getTemplate($batch);

            $insert[] = [
                'batch' => $batch,
                'templateId' => $templateId,
                'email' => $user['email'],
                'sorting' => rand(),
            ];
            ++$processed;
            if ($processed == $batchInsert) {
                $processed = 0;
                $this->mailJobQueueRepository->multiInsert($insert);
                $insert = [];
            }
        }

        if ($processed) {
            $this->mailJobQueueRepository->multiInsert($insert);
        }

        $this->mailJobQueueRepository->removeUnsubscribed($batch);
        if ($job->mail_type_variant_id) {
            $this->mailJobQueueRepository->removeOtherVariants($batch, $job->mail_type_variant_id);
        }
        $this->mailJobQueueRepository->removeAlreadyQueued($batch);
        $this->mailJobQueueRepository->removeAlreadySent($batch);
        $this->mailJobQueueRepository->stripEmails($batch, $batch->max_emails);

        $queueJobs = $this->mailJobQueueRepository->getBatchEmails($batch, 0, null);
        $this->mailCache->pauseQueue($batch->id);
        foreach ($queueJobs as $job) {
            $template = $job->ref('mail_templates', 'mail_template_id');
            $this->mailCache->addJob($job->email, $template->code, $job->mail_batch_id);
        }
        $this->mailCache->restartQueue($batch->id);
    }

    private function getTemplate(IRow $batch)
    {
        if (isset($this->templates[$batch->id])) {
            return $this->templates[$batch->id][ array_rand($this->templates[$batch->id]) ];
        }

        $this->templates[$batch->id] = [];

        $templates = $batch->related('mail_job_batch_templates');
        foreach ($templates as $template) {
            $this->templates[$batch->id] = array_merge($this->templates[$batch->id], array_fill(0, $template->weight, $template->mail_template_id));
        }

        return $this->templates[$batch->id][ array_rand($this->templates[$batch->id]) ];
    }
}
