<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Exception;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Job\BatchEmailGenerator;
use Remp\MailerModule\Repositories\BatchesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Tracy\ILogger;

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

    protected function configure(): void
    {
        $this->setName('mail:process-job')
            ->setDescription('Process job command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '256M');
        $output->writeln(sprintf('%s <info>Mail process job</info>', DateTime::from('now')));

        $pid = getmypid();

        while ($batch = $this->batchesRepository->getBatchReady()) {
            try {
                $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_PROCESSING);
                $output->writeln("  * processing mail batch <info>#{$batch->id}</info>");

                if ($batch->related('mail_job_batch_templates')->count('*') == 0) {
                    $output->writeln("<error>Batch #{$batch->id} has no templates</error>");
                    $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_CREATED);
                    continue;
                }

                $this->batchEmailGenerator->generate($batch);
                $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_PROCESSED);
                $this->batchesRepository->update($batch, [
                    'pid' => $pid,
                ]);
            } catch (Exception $e) {
                Debugger::log($e, ILogger::ERROR);
                $reschedule = DateTime::from('+5 minutes');
                $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_READY);
                $this->batchesRepository->update($batch, [
                    'start_at' => $reschedule,
                ]);
                $output->writeln("  * <error>processing failed</error>: {$e->getMessage()}; rescheduling to <info>{$reschedule->format(DATE_RFC3339)}</info>");
            }
        }

        $output->writeln('  * no batch to process');

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
