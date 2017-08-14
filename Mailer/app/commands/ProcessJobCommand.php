<?php

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\Job\BatchEmailGenerator;
use Remp\MailerModule\Job\JobProcess;
use Remp\MailerModule\Repository\BatchesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessJobCommand extends Command
{
    private $batchesRepository;

    private $batchEmailGenerator;

    public function __construct(
        BatchesRepository $batchesRepository,
        BatchEmailGenerator $batchEmailGenerator
    ) {
        parent::__construct();
        $this->batchesRepository = $batchesRepository;
        $this->batchEmailGenerator = $batchEmailGenerator;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('mail:process-job')
            ->setDescription('Process job command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** EMAIL JOB *****</info>');
        $output->writeln('');

        $process = new JobProcess();
        $pid = $process->pid();

        while ($batch = $this->batchesRepository->getBatchReady()) {
            $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATE_PROCESSING]);
            $output->writeln("Processing mail batch <info>#{$batch->id}</info>");

            if ($batch->related('mail_job_batch_templates')->count('*') == 0) {
                $output->writeln("<error>Batch #{$batch->id} has no templates</error>");
                $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATE_CREATED]);
                continue;
            }

            $this->batchEmailGenerator->generate($batch);
            $this->batchesRepository->update($batch, [
                'status' => BatchesRepository::STATE_PROCESSED,
                'pid' => $pid,
            ]);
        }

        $output->writeln('No batch to process');

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }
}
