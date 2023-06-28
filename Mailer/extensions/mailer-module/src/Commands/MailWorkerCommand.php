<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Exception;
use League\Event\EventDispatcher;
use Nette\Mail\SmtpException;
use Nette\Utils\DateTime;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Remp\MailerModule\Events\MailSentEvent;
use Remp\MailerModule\Models\HealthChecker;
use Remp\MailerModule\Models\Job\MailCache;
use Remp\MailerModule\Models\Mailer\EmailAllowList;
use Remp\MailerModule\Models\Sender;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Tomaj\Hermes\Shutdown\ShutdownInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class MailWorkerCommand extends Command
{
    public const COMMAND_NAME = "worker:mail";
    
    private $applicationMailer;

    private $mailJobBatchRepository;

    private $mailJobQueueRepository;

    private $mailLogRepository;

    private $mailTemplateRepository;

    private $batchTemplatesRepository;

    private $mailCache;

    private $emailAllowList;

    private $eventDispatcher;

    private $isFirstLine = true;

    private $smtpErrors = 0;

    private $logger;

    /** @var ShutdownInterface */
    private $shutdown;

    /** @var DateTime */
    private $startTime;

    private HealthChecker $healthChecker;

    public function __construct(
        LoggerInterface $logger,
        Sender $applicationMailer,
        BatchesRepository $mailJobBatchRepository,
        JobQueueRepository $mailJobQueueRepository,
        LogsRepository $mailLogRepository,
        TemplatesRepository $mailTemplatesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        MailCache $redis,
        EmailAllowList $emailAllowList,
        EventDispatcher $eventDispatcher,
        HealthChecker $healthChecker
    ) {
        parent::__construct();
        $this->applicationMailer = $applicationMailer;
        $this->mailJobBatchRepository = $mailJobBatchRepository;
        $this->mailJobQueueRepository = $mailJobQueueRepository;
        $this->mailLogRepository = $mailLogRepository;
        $this->mailTemplateRepository = $mailTemplatesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->mailCache = $redis;
        $this->emailAllowList = $emailAllowList;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->healthChecker = $healthChecker;
    }

    /**
     * Set implementation of ShutdownInterface which should handle graceful shutdowns.
     *
     * @param ShutdownInterface $shutdown
     */
    public function setShutdownInterface(ShutdownInterface $shutdown): void
    {
        $this->shutdown = $shutdown;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Start worker sending mails')
            ->addOption(
                'batch',
                'b',
                InputOption::VALUE_NONE,
                'Flag whether batch sending should be attempted (will fallback to non-batch if selected mailer doesn\'t support batch sending)'
            )
            ->addOption(
                'batch-size',
                's',
                InputOption::VALUE_REQUIRED,
                'Size of the batch to be used for sending. Applies only when --batch is used.',
                200
            )

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // store when command was started
        $this->startTime = new DateTime();

        $sendAsBatch = $input->getOption('batch');

        $messagesPerBatch = (int) $input->getOption('batch-size');
        if ($messagesPerBatch <= 0) {
            throw new RuntimeException("The --batch-size option only allows positive integers.");
        }

        $output->writeln('');
        $output->writeln('<info>***** EMAIL WORKER *****</info>');
        $output->writeln('');

        $output->write('Checking mail queues');

        while (true) {
            // graceful shutdown check
            if ($this->shutdown && $this->shutdown->shouldShutdown($this->startTime)) {
                $now = (new DateTime())->format(DATE_RFC3339);
                $msg = "Exiting mail worker: shutdown instruction received '{$now}'.";
                $output->write("\n<comment>{$msg}</comment>\n");
                $this->logger->info($msg);
                return 0;
            }

            $this->healthChecker->ping(self::COMMAND_NAME);

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
                $this->stopBatchSending($batch, BatchesRepository::STATUS_DONE);
                continue;
            }

            if ($batch->status === BatchesRepository::STATUS_QUEUED) {
                $this->mailJobBatchRepository->updateStatus($batch, BatchesRepository::STATUS_SENDING);
            }

            $output->writeln("Sending batch <info>{$batch->id}</info>...");
            $this->logger->info("Sending batch <info>{$batch->id}</info>...");

            while (true) {
                $this->healthChecker->ping(self::COMMAND_NAME);

                if (!$this->mailCache->isQueueActive($batch->id)) {
                    $output->writeln("Queue <info>{$batch->id}</info> not active anymore...");
                    $this->logger->info("Queue <info>{$batch->id}</info> not active anymore...");
                    break;
                }
                if (!$this->mailCache->isQueueTopPriority($batch->id)) {
                    $topPriorityQueueScore = $this->mailCache->getTopPriorityQueues();
                    $topPriorityBatchId = array_key_first($topPriorityQueueScore);

                    $output->writeln("Batch <info>{$batch->id}</info> no longer top priority, switching to <info>$topPriorityBatchId</info>...");
                    $this->logger->info("Batch <info>{$batch->id}</info> no longer top priority, switching to <info>$topPriorityBatchId</info>...");

                    if (!$this->mailJobBatchRepository->isSendingBatch($topPriorityBatchId)) {
                        $this->mailCache->removeQueue($topPriorityBatchId);
                        $output->writeln("Top priority batch <info>{$topPriorityBatchId}</info> is not being sent anymore, clearing cache and starting again...");
                        $this->logger->info("Top priority batch <info>{$topPriorityBatchId}</info> is not being sent anymore, clearing cache and starting again...");
                    }

                    break;
                }

                $mailJobBatchTemplate = $this->batchTemplatesRepository->findByBatchId($batch->id)->fetch();
                if (!$mailJobBatchTemplate) {
                    $this->stopBatchSending($batch, BatchesRepository::STATUS_WORKER_STOP);
                    $this->logger->error('Mail job batch template not found for batch: ' . $batch->id);
                    continue 2;
                }

                if ($sendAsBatch && $this->applicationMailer->getMailerByTemplate($mailJobBatchTemplate->mail_template)->supportsBatch()) {
                    $rawJobs = $this->mailCache->getJobs($batch->id, $messagesPerBatch);
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
                    $jobs = $this->filterJobs($jobs, $batch);
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

                    $template = null;

                    $output->writeln(" * Processing $jobsCount jobs of template <info>{$templateCode}</info>, batch <info>{$batch->id}</info>");
                    $this->logger->info(" * Processing $jobsCount jobs of template <info>{$templateCode}</info>, batch <info>{$batch->id}</info>");
                    foreach ($jobs as $i => $job) {
                        if (!$template) {
                            $template = $this->mailTemplateRepository->getByCode($job->templateCode);
                        }

                        $output->writeln(" * sending <info>{$job->templateCode}</info> from batch <info>{$batch->id}</info> to <info>{$job->email}</info>");
                        $recipientParams = $job->params ? get_object_vars($job->params) : [];
                        $email->addRecipient($job->email, null, $recipientParams);
                        if ($job->context) {
                            $email->setContext($job->context);
                        }
                    }

                    $sentCount = 0;

                    try {
                        $email = $email->setTemplate($template);
                        if ($sendAsBatch && $email->supportsBatch()) {
                            $output->writeln("sending {$templateCode} (batch {$batch->id}) as a batch");

                            try {
                                $sentCount = $email->sendBatch($this->logger);
                            } catch (Throwable $throwable) {
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

                        foreach ($jobs as $job) {
                            $this->eventDispatcher->dispatch(new MailSentEvent($job->userId, $job->email, $job->templateCode, $batch->id, time()));
                        }

                        $this->smtpErrors = 0;
                    } catch (SmtpException | Sender\MailerBatchException | Exception $exception) {
                        $this->smtpErrors++;
                        $output->writeln("<error>Sending error: {$exception->getMessage()}</error>");
                        Debugger::log($exception, ILogger::WARNING);

                        $this->logger->warning("Unable to send an email: " . $exception->getMessage(), [
                            'batch' => $sendAsBatch && $email->supportsBatch(),
                            'template' => $templateCode,
                        ]);

                        $this->cacheJobs($jobs, $batch->id);

                        if ($this->smtpErrors >= 10) {
                            $this->mailJobBatchRepository->updateStatus($batch, BatchesRepository::STATUS_WORKER_STOP);
                            break;
                        }
                        sleep(10);
                    }

                    $first_email = $batch->first_email_sent_at ? new DateTime($batch->first_email_sent_at) : null;
                    $now = new DateTime();

                    // update stats
                    $this->mailJobBatchRepository->update($batch, [
                        'first_email_sent_at' => $first_email,
                        'last_email_sent_at' => $now,
                        'sent_emails+=' => $sentCount,
                        'last_ping' => $now
                    ]);

                    $jobBatchTemplate = $this->batchTemplatesRepository->getTable()->where([
                        'mail_template_id' => $template->id,
                        'mail_job_batch_id' => $batch->id,
                    ])->fetch();
                    $this->batchTemplatesRepository->update($jobBatchTemplate, [
                        'sent+=' => $sentCount,
                    ]);
                }
            }
        }

        return Command::SUCCESS;
    }

    private function cacheJobs(array $jobs, int $batchId): void
    {
        foreach ($jobs as $job) {
            $this->mailCache->addJob($job->userId, $job->email, $job->templateCode, $batchId, $job->context, (array) ($job->params ?? []));
        }
    }

    private function filterJobs(array $jobs, ActiveRow $batch): array
    {
        $mailTemplates = [];

        $emailsByTemplateCodes = [];
        $jobsByEmails = [];
        foreach ($jobs as $i => $job) {
            if (!isset($mailTemplates[$job->templateCode])) {
                $mailTemplates[$job->templateCode] = $this->mailTemplateRepository->getByCode($job->templateCode);
            }
            $emailsByTemplateCodes[$job->templateCode][] = $job->email;
            $jobsByEmails[$job->email] = $job;
        }

        // get list of allowed emails
        $filteredEmails = [];
        foreach ($emailsByTemplateCodes as $templateCode => $emails) {
            $filteredTemplateEmails = $this->mailLogRepository->filterAlreadySentV2(
                emails: $emails,
                mailTemplate: $mailTemplates[$templateCode],
                job: $batch->mail_job,
                context: $job->context ?? null,
            );
            $filteredEmails = array_merge($filteredEmails, $filteredTemplateEmails);
        }

        // extract list of allowed jobs based on allowed emails
        $filteredJobs = [];
        foreach ($filteredEmails as $filteredEmail) {
            if ($this->emailAllowList->isAllowed($filteredEmail)) {
                $filteredJobs[] = $jobsByEmails[$filteredEmail];
            }
        }

        return $filteredJobs;
    }

    private function stopBatchSending(ActiveRow $batch, string $batchStatus): void
    {
        $this->mailCache->removeQueue($batch->id);
        $this->mailJobBatchRepository->updateStatus($batch, $batchStatus);
        $this->mailJobQueueRepository->clearBatch($batch);
    }
}
