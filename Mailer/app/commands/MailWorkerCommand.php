<?php

namespace Remp\MailerModule\Commands;

use League\Event\Emitter;
use Nette\Mail\SmtpException;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Remp\MailerModule\Events\MailSentEvent;
use Remp\MailerModule\Job\MailCache;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobQueueRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Sender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MailWorkerCommand extends Command
{
    const MESSAGES_PER_BATCH = 200;

    private $applicationMailer;

    private $mailJobsRepository;

    private $mailJobBatchRepository;

    private $mailJobQueueRepository;

    private $mailLogRepository;

    private $mailTemplateRepository;

    private $batchTemplatesRepository;

    private $mailCache;

    private $emitter;

    private $isFirstLine = true;

    private $smtpErrors = 0;

    public function __construct(
        Sender $applicationMailer,
        JobsRepository $mailJobsRepository,
        BatchesRepository $mailJobBatchRepository,
        JobQueueRepository $mailJobQueueRepository,
        LogsRepository $mailLogRepository,
        TemplatesRepository $mailTemplatesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        MailCache $redis,
        Emitter $emitter
    ) {
        parent::__construct();
        $this->applicationMailer = $applicationMailer;
        $this->mailJobsRepository = $mailJobsRepository;
        $this->mailJobBatchRepository = $mailJobBatchRepository;
        $this->mailJobQueueRepository = $mailJobQueueRepository;
        $this->mailLogRepository = $mailLogRepository;
        $this->mailTemplateRepository = $mailTemplatesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->mailCache = $redis;
        $this->emitter = $emitter;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('worker:mail')
            ->setDescription('Start worker sending mails')
            ->addOption(
                'batch',
                'b',
                InputOption::VALUE_NONE,
                'Flag whether batch sending should be attempted (will fallback to non-batch if selected mailer doesn\'t support batch sending)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sendAsBatch = $input->getOption('batch');

        $output->writeln('');
        $output->writeln('<info>***** EMAIL WORKER *****</info>');
        $output->writeln('');

        $output->write('Checking mail queues');

        while (true) {
            $batch = $this->mailJobBatchRepository->getBatchToSend();
            if (!$batch) {
                sleep(30);
                $output->write('.');
                $this->isFirstLine = true;
                continue;
            }

            if ($this->isFirstLine) {
                $output->writeln('');
                $this->isFirstLine = false;
            }

            if (!$this->mailCache->hasJobs($batch->id)) {
                $output->writeln("Queue <info>{$batch->id}</info> has no more jobs, cleaning up...");
                $this->mailCache->removeQueue($batch->id);
                $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATE_DONE]);
                continue;
            }

            if ($batch->status == BatchesRepository::STATE_PROCESSED) {
                $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATE_SENDING]);
            }

            $output->writeln("Sending batch <info>{$batch->id}</info>...");

            while (true) {
                if (!$this->mailCache->isQueueActive($batch->id)) {
                    $output->writeln("Queue <info>{$batch->id}</info> not active anymore...");
                    break;
                }
                if (!$this->mailCache->isQueueTopPriority($batch->id)) {
                    $output->writeln("Batch <info>{$batch->id}</info> no longer top priority, switching...");
                    break;
                }

                if ($sendAsBatch) {
                    $rawJobs = $this->mailCache->getJobs($batch->id, self::MESSAGES_PER_BATCH);
                    if (empty($rawJobs)) {
                        break;
                    }
                } else {
                    $rawJobs = $this->mailCache->getJob($batch->id);
                    if (!$rawJobs) {
                        break;
                    }
                    $rawJobs = [$rawJobs];
                }

                $jobsByTemplateCode = [];
                foreach ($rawJobs as $rawJob) {
                    $job = Json::decode($rawJob);
                    $jobsByTemplateCode[$job->templateCode][] = $job;
                }

                foreach ($jobsByTemplateCode as $templateCode => $jobs) {
                    $jobs = $this->filterAlreadySentJobs($jobs, $batch);
                    if (empty($jobs)) {
                        continue;
                    }

                    $email = $this->applicationMailer
                        ->reset()
                        ->setJobId($batch->mail_job_id)
                        ->setBatchId($batch->id)
                        ->setParams([]);

                    $queueJobs = [];
                    $template = null;

                    foreach ($jobs as $i => $job) {
                        $queueJob = $this->mailJobQueueRepository->getJob($job->email, $batch->id);
                        $queueJobs[$i] = $queueJob;

                        if (!$template) {
                            $template = $this->mailTemplateRepository->getByCode($job->templateCode);
                        }

                        $output->writeln(" * sending <info>{$job->templateCode}</info> from batch <info>{$batch->id}</info> to <info>{$job->email}</info>");
                        $email->addRecipient($job->email);
                    }

                    try {
                        $email = $email->setTemplate($template);
                        if ($sendAsBatch && $email->supportsBatch()) {
                            $result = $email->sendBatch();
                        } else {
                            $result = $email->send();
                        }

                        if ($result) {
                            foreach ($jobs as $i => $job) {
                                $this->mailJobQueueRepository->delete($queueJobs[$i]);
                                $this->emitter->emit(new MailSentEvent($job->userId, $job->email, $job->templateCode, $batch->id, time()));
                            }
                        } else {
                            $this->mailJobBatchRepository->update($batch, ['errors_count+=' => count($jobs)]);
                            $this->mailJobQueueRepository->update($queueJob, ['status' => JobQueueRepository::STATUS_ERROR]);
                        }
                        $this->smtpErrors = 0;
                    } catch (SmtpException | Sender\MailerBatchException $exception) {
                        $this->smtpErrors++;
                        $output->writeln("<error>Sending error: {$exception->getMessage()}</error>");
                        $this->cacheJobs($jobs, $batch->id);

                        if ($this->smtpErrors >= 10) {
                            $this->mailCache->pauseQueue($batch->id);
                            $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATE_WORKER_STOP]);
                            break;
                        }
                        sleep(10);
                    }

                    $first_email = new DateTime($batch->first_email_sent_at);
                    $now = new DateTime();

                    $this->mailJobBatchRepository->update($batch, [
                        'first_email_sent_at' => $first_email,
                        'last_email_sent_at' => $now,
                        'sent_emails+=' => count($jobs),
                        'last_ping' => $now
                    ]);

                    $jobBatchTemplate = $this->batchTemplatesRepository->getTable()->where([
                        'mail_template_id' => $template->id,
                        'mail_job_batch_id' => $batch->id,
                    ])->fetch();
                    $this->batchTemplatesRepository->update($jobBatchTemplate, [
                        'sent+=' => count($jobs),
                    ]);
                }
            }
        }
    }

    private function cacheJobs($jobs, $batchId)
    {
        foreach ($jobs as $job) {
            $this->mailCache->addJob($job->userId, $job->email, $job->templateCode, $batchId, $job->context);
        }
    }

    private function filterAlreadySentJobs($jobs, $batch)
    {
        $emailsByTemplateCodes = [];
        $jobsByEmails = [];
        foreach ($jobs as $i => $job) {
            $emailsByTemplateCodes[$job->templateCode][] = $job->email;
            $jobsByEmails[$job->email] = $job;
        }

        // get list of allowed emails
        $filteredEmails = [];
        foreach ($emailsByTemplateCodes as $templateCode => $emails) {
            $filteredTemplateEmails = $this->mailLogRepository->filterAlreadySent($emails, $templateCode, $batch->mail_job_id);
            $filteredEmails = array_merge($filteredEmails, $filteredTemplateEmails);
        }

        // extract list of allowed jobs based on allowed emails
        $filteredJobs = [];
        foreach ($filteredEmails as $filteredEmail) {
            $filteredJobs[] = $jobsByEmails[$filteredEmail];
        }

        return $filteredJobs;
    }
}
