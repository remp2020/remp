<?php

namespace Remp\MailerModule\Commands;

use League\Event\Emitter;
use Nette\DI\Container;
use Nette\Mail\SmtpException;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
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

    private $container;

    private $logger;

    public function __construct(
        LoggerInterface $logger,
        Sender $applicationMailer,
        JobsRepository $mailJobsRepository,
        BatchesRepository $mailJobBatchRepository,
        JobQueueRepository $mailJobQueueRepository,
        LogsRepository $mailLogRepository,
        TemplatesRepository $mailTemplatesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        MailCache $redis,
        Emitter $emitter,
        Container $container
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
        $this->container = $container;
        $this->logger = $logger;
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
                $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATUS_DONE]);
                continue;
            }

            if ($batch->status == BatchesRepository::STATUS_PROCESSED) {
                $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATUS_SENDING]);
            }

            $output->writeln("Sending batch <info>{$batch->id}</info>...");
            $this->logger->info("Sending batch <info>{$batch->id}</info>...");

            while (true) {
                if (!$this->mailCache->isQueueActive($batch->id)) {
                    $output->writeln("Queue <info>{$batch->id}</info> not active anymore...");
                    $this->logger->info("Queue <info>{$batch->id}</info> not active anymore...");
                    break;
                }
                if (!$this->mailCache->isQueueTopPriority($batch->id)) {
                    $output->writeln("Batch <info>{$batch->id}</info> no longer top priority, switching...");
                    $this->logger->info("Batch <info>{$batch->id}</info> no longer top priority, switching...");
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
                    $originalCount = count($jobs);
                    $jobs = $this->filterAlreadySentJobs($jobs, $batch);
                    $jobsCount = count($jobs);
                    $filteredCount = $originalCount - $jobsCount;

                    if ($filteredCount > 0) {
                        $output->writeln(" * $filteredCount jobs of $originalCount were filtered for template <info>{$templateCode}</info>, <info>{$batch->id}</info>");
                        $this->logger->info(" * $filteredCount jobs of $originalCount were filtered for template <info>{$templateCode}</info>, <info>{$batch->id}</info>");
                    }

                    if (empty($jobs)) {
                        continue;
                    }

                    $email = $this->applicationMailer
                        ->reset()
                        ->setJobId($batch->mail_job_id)
                        ->setBatchId($batch->id);

                    $queueJobs = [];
                    $template = null;

                    $output->writeln(" * Processing $jobsCount jobs of template <info>{$templateCode}</info>, batch <info>{$batch->id}</info>");
                    $this->logger->info(" * Processing $jobsCount jobs of template <info>{$templateCode}</info>, batch <info>{$batch->id}</info>");
                    foreach ($jobs as $i => $job) {
                        $queueJob = $this->mailJobQueueRepository->getJob($job->email, $batch->id);
                        $queueJobs[$i] = $queueJob;

                        if (!$template) {
                            $template = $this->mailTemplateRepository->getByCode($job->templateCode);
                        }

                        $output->writeln(" * sending <info>{$job->templateCode}</info> from batch <info>{$batch->id}</info> to <info>{$job->email}</info>");
                        $recipientParams = $job->params ? get_object_vars($job->params) : [];
                        $email->addRecipient($job->email, null, $recipientParams);
                        $email->setContext($job->context);
                    }

                    $sentCount = 0;

                    try {
                        $email = $email->setTemplate($template);
                        if ($sendAsBatch && $email->supportsBatch()) {
                            $output->writeln("sending {$templateCode} (batch {$batch->id}) as a batch");

                            // TODO temporarily trying to catch all possible errors to debug https://gitlab.com/remp/remp/issues/502
                            // remove once it's fixed
                            try {
                                $sentCount = $email->sendBatch($this->logger);
                            } catch (\Throwable $throwable) {
                                $this->logger->warning('Unexpected error occurred while sending batch ', [
                                    'message' => $throwable->getMessage(),
                                    'throwable' => $throwable,
                                ]);
                                throw $throwable;
                            }
                        } else {
                            $sentCount = $email->send();
                        }

                        $output->writeln(" * $sentCount mail(s) of batch <info>{$batch->id}</info> sent");
                        $this->logger->info(" * $sentCount mail(s) of batch <info>{$batch->id}</info> sent");

                        foreach ($jobs as $i => $job) {
                            $this->mailJobQueueRepository->delete($queueJobs[$i]);
                            $this->emitter->emit(new MailSentEvent($job->userId, $job->email, $job->templateCode, $batch->id, time()));
                        }

                        $this->smtpErrors = 0;
                    } catch (SmtpException | Sender\MailerBatchException | \Exception $exception) {
                        $this->smtpErrors++;
                        $output->writeln("<error>Sending error: {$exception->getMessage()}</error>");

                        $this->logger->warning("Unable to send an email: " . $exception->getMessage(), [
                            'batch' => $sendAsBatch && $email->supportsBatch(),
                            'template' => $templateCode,
                        ]);

                        $this->cacheJobs($jobs, $batch->id);

                        if ($this->smtpErrors >= 10) {
                            $this->mailCache->pauseQueue($batch->id);
                            $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATUS_WORKER_STOP]);
                            break;
                        }
                        sleep(10);
                    }

                    $first_email = new DateTime($batch->first_email_sent_at);
                    $now = new DateTime();

                    $this->mailJobBatchRepository->update($batch, [
                        'first_email_sent_at' => $first_email,
                        'last_email_sent_at' => $now,
                        'sent_emails+=' => (int) $sentCount,
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
            $this->mailCache->addJob($job->userId, $job->email, $job->templateCode, $batchId, $job->context, $job->params ?? []);
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
