<?php

namespace Remp\MailerModule\Commands;

use Exception;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Job\MailCache;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveOldMailJobBatchesCommand extends Command
{
    public const COMMAND_NAME = "mail:remove-old-batches";

    private BatchesRepository $batchesRepository;

    private MailCache $mailCache;

    private LogsRepository $logsRepository;
    private BatchTemplatesRepository $batchTemplatesRepository;
    private JobQueueRepository $jobQueueRepository;

    public function __construct(
        BatchesRepository $batchesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        JobQueueRepository $jobQueueRepository,
        LogsRepository $logsRepository,
        MailCache $redis
    ) {
        parent::__construct();
        $this->batchesRepository = $batchesRepository;
        $this->mailCache = $redis;
        $this->logsRepository = $logsRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->jobQueueRepository = $jobQueueRepository;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Removes old mail job batches');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('%s <info>Remove old mail batches worker</info>', DateTime::from('now')));

        while ($batch = $this->batchesRepository->getBatchToRemove()) {
            $sentCount = $this->logsRepository->getTable()->where([
                'mail_job_batch_id' => $batch->id,
            ])->count('*');
            if ($sentCount > 0) {
                throw new Exception("Job batch ID: {$batch->id} can't be deleted. Some emails were already sent.");
            }

            $output->writeln("  * removing mail batch <info>#{$batch->id}</info>");

            $this->batchTemplatesRepository->deleteByBatchId($batch->id);
            $this->mailCache->removeQueue($batch->id);
            $this->jobQueueRepository->deleteJobsByBatch($batch->id, true);
            $this->batchesRepository->delete($batch);
        }

        $output->writeln('  * no batch to process');

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
