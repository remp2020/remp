<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Exception;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\HealthChecker;
use Remp\MailerModule\Models\Job\BatchEmailGenerator;
use Remp\MailerModule\Repositories\BatchesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class ProcessJobCommand extends Command
{
    public const COMMAND_NAME = "mail:process-job";
    private int $healthCheckTTLSeconds = 600;

    public function __construct(
        private readonly BatchesRepository $batchesRepository,
        private readonly BatchEmailGenerator $batchEmailGenerator,
        private readonly HealthChecker $healthChecker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Process job command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('%s <info>Mail process job</info>', DateTime::from('now')));

        $pid = getmypid();

        while ($batch = $this->batchesRepository->getBatchReady()) {
            $originalStatus = $batch->status;
            try {
                $this->healthChecker->ping(self::COMMAND_NAME, $this->healthCheckTTLSeconds);

                $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_PROCESSING);
                $output->writeln("  * processing mail batch <info>#{$batch->id}</info>");

                if ($batch->related('mail_job_batch_templates')->count('*') === 0) {
                    $output->writeln("<error>Batch #{$batch->id} has no templates</error>");
                    $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_CREATED);
                    continue;
                }

                $this->batchEmailGenerator->generate($batch);
                if ($originalStatus === BatchesRepository::STATUS_READY_TO_PROCESS) {
                    $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_PROCESSED);
                } else {
                    $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_QUEUED);
                }
                $this->batchesRepository->update($batch, [
                    'pid' => $pid,
                ]);
            } catch (Exception $e) {
                Debugger::log($e, ILogger::ERROR);
                $reschedule = DateTime::from('+5 minutes');
                $this->batchesRepository->updateStatus($batch, $originalStatus);
                $this->batchesRepository->update($batch, [
                    'start_at' => $reschedule,
                ]);
                $output->writeln("  * <error>processing failed</error>: {$e->getMessage()}; rescheduling to <info>{$reschedule->format(DATE_RFC3339)}</info>");
            }
        }

        $output->writeln('  * no batch to process');

        $this->healthChecker->ping(self::COMMAND_NAME);

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');

        return Command::SUCCESS;
    }

    public function setHealthCheckTTLSeconds(int $seconds): void
    {
        $this->healthCheckTTLSeconds = $seconds;
    }
}
