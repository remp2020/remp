<?php

namespace Remp\MailerModule\Commands;

use Nette\Mail\SmtpException;
use Nette\Utils\DateTime;
use Remp\MailerModule\Job\MailCache;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\JobQueueRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Sender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailWorkerCommand extends Command
{
    private $applicationMailer;

    private $mailJobsRepository;

    private $mailJobBatchRepository;

    private $mailJobQueueRepository;

    private $mailLogRepository;

    private $mailTemplateRepository;

    private $mailCache;

    private $isFirstLine = true;

    private $smtpErrors = 0;

    public function __construct(
        Sender $applicationMailer,
        JobsRepository $mailJobsRepository,
        BatchesRepository $mailJobBatchRepository,
        JobQueueRepository $mailJobQueueRepository,
        LogsRepository $mailLogRepository,
        TemplatesRepository $mailTemplatesRepository,
        MailCache $redis
    ) {
        parent::__construct();
        $this->applicationMailer = $applicationMailer;
        $this->mailJobsRepository = $mailJobsRepository;
        $this->mailJobBatchRepository = $mailJobBatchRepository;
        $this->mailJobQueueRepository = $mailJobQueueRepository;
        $this->mailLogRepository = $mailLogRepository;
        $this->mailTemplateRepository = $mailTemplatesRepository;
        $this->mailCache = $redis;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('worker:mail')
            ->setDescription('Start mail worker')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

            if (!$this->mailCache->hasJobs($batch->id)) {
                $this->mailCache->removeQueue($batch->id);
                $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATE_DONE]);
                continue;
            }

            if ($batch->status == BatchesRepository::STATE_PROCESSED) {
                $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATE_SENDING]);
            }

            while ($job = json_decode($this->mailCache->getJob($batch->id))) {
                if (!$this->mailCache->isQueueActive($batch->id)) {
                    break;
                }

                $queueJob = $this->mailJobQueueRepository->getJob($job->email, $batch->id);

                if ($this->isDuplicateJob($job->email, $job->templateCode, $batch->mail_job_id)) {
                    $this->mailJobQueueRepository->delete($queueJob);
                    continue;
                }

                if ($this->isFirstLine) {
                    $output->writeln('');
                    $this->isFirstLine = false;
                }

                $output->writeln(" * sending from batch <info>{$batch->id}</info> to <info>{$job->email}</info>");

                try {
                    $template = $this->mailTemplateRepository->getByCode($job->templateCode);
                    $result = $this->applicationMailer->setTemplate($template)
                        ->setRecipient($job->email)
                        ->setParams([])
                        ->send();

                    if ($result) {
                        $this->mailJobQueueRepository->delete($queueJob);
                    } else {
                        $this->mailJobBatchRepository->update($batch, ['errors_count+=' => 1]);
                        $this->mailJobQueueRepository->update($queueJob, ['status' => JobQueueRepository::STATUS_ERROR]);
                    }
                    $this->smtpErrors = 0;
                } catch (SmtpException $smtpException) {
                    $this->smtpErrors++;
                    $output->writeln("<error>SMTP Error {$smtpException->getMessage()}</error>");
                    $this->mailCache->addJob($job->email, $job->templateCode, $batch->id);

                    if ($this->smtpErrors >= 10) {
                        $this->mailCache->pauseQueue($batch->id);
                        $this->mailJobBatchRepository->update($batch, ['status' => BatchesRepository::STATE_WORKER_STOP]);
                        break;
                    }
                }

                $first_email = new DateTime($batch->first_email_sent_at);
                $now = new DateTime();

                $this->mailJobBatchRepository->update($batch, [
                    'first_email_sent_at' => $first_email,
                    'last_email_sent_at' => $now,
                    'sent_emails+=' => 1,
                    'last_ping' => $now
                ]);
            }
        }
    }

    private function isDuplicateJob($email, $templateCode, $mailJobId)
    {
        if ($this->mailLogRepository->alreadySentForJob($email, $mailJobId)) {
            return true;
        }
        if ($this->mailLogRepository->alreadySentForEmail($templateCode, $email)) {
            return true;
        }

        return false;
    }
}
