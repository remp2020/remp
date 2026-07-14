<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\MailLogsStatsStateRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessJobStatsCommand extends Command
{
    private $logsRepository;

    private $batchTemplatesRepository;

    private $mailLogsStatsStateRepository;

    public function __construct(
        LogsRepository $logsRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        MailLogsStatsStateRepository $mailLogsStatsStateRepository,
    ) {
        parent::__construct();
        $this->logsRepository = $logsRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->mailLogsStatsStateRepository = $mailLogsStatsStateRepository;
    }

    protected function configure(): void
    {
        $this->setName('mail:job-stats')
            ->setDescription('Process job stats based on mail logs')
            ->addOption(
                'only-converted',
                null,
                InputOption::VALUE_NONE,
                'Gets batch template stats only for column converted'
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'Only process batches with mail log activity after selected date',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $onlyConverted = (bool) $input->getOption('only-converted');

        $from = null;
        try {
            if ($input->getOption('from')) {
                $from = DateTime::from($input->getOption('from'));
            }
        } catch (\Exception $e) {
            $output->writeln("<error>Invalid value for --from option: {$e->getMessage()}</error>");
            return self::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>***** UPDATE EMAIL JOB STATS *****</info>');
        $output->writeln('');

        $cutoffDate = $this->mailLogsStatsStateRepository->getCutoffDate();
        if ($cutoffDate !== null) {
            $output->writeln("<info>Stats cutoff active ({$cutoffDate->format(DATE_RFC3339)}): batches entirely older than it are sealed and skipped.</info>");
        }

        // With only option converted in command, this update executes in every run
        $output->write('Updating column converted: ');
        $this->batchTemplatesRepository->updateAllConverted($cutoffDate);
        $output->writeln('<info>OK!</info>');

        if (!$onlyConverted) {
            if ($input->getOption('from')) {
                $output->writeln("Looking for batches with activity after <comment>{$from->format(DATE_RFC3339)}.</comment>");

                $batchIds = $this->logsRepository->getTable()
                    ->select('mail_job_batch_id')
                    ->where('updated_at >= ?', $from)
                    ->where('mail_job_batch_id IS NOT NULL')
                    ->group('mail_job_batch_id')
                    ->fetchPairs('mail_job_batch_id', 'mail_job_batch_id');

                if (empty($batchIds)) {
                    $output->writeln('<info>Nothing to do, exiting.</info>');
                    return Command::SUCCESS;
                }

                $batchTemplatesQuery = $this->batchTemplatesRepository->findByBatchIds($batchIds);
            } else {
                $batchTemplatesQuery = $this->batchTemplatesRepository->getTable();
            }

            if ($cutoffDate !== null) {
                $batchTemplatesQuery->where(
                    'mail_job_batch.first_email_sent_at >= ?',
                    $cutoffDate
                );
            }

            $batchTemplatesCount = (clone $batchTemplatesQuery)->count('*');
            if ($batchTemplatesCount === 0) {
                $output->writeln('<info>Nothing to do, exiting.</info>');
                return Command::SUCCESS;
            }

            ProgressBar::setFormatDefinition(
                'processStats',
                "%processing% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
            );

            $progressBar = new ProgressBar($output, $batchTemplatesCount);
            $progressBar->setFormat('processStats');
            $progressBar->start();

            $batchTemplatesQuery->limit(1000)->order('mail_job_batch_templates.id ASC');
            $lastId = 0;

            while ($batchTemplates = (clone $batchTemplatesQuery)->where('mail_job_batch_templates.id > ?', $lastId)->fetchAll()) {
                foreach ($batchTemplates as $batchTemplate) {
                    $lastId = $batchTemplate->id;
                    $progressBar->setMessage('Processing jobBatchTemplate <info>' . $batchTemplate->id . '</info>', 'processing');
                    $stats = $this->logsRepository->getBatchTemplateStats($batchTemplate);

                    $this->batchTemplatesRepository->update($batchTemplate, [
                        'delivered' => $stats->delivered ?? 0,
                        'opened' => $stats->opened ?? 0,
                        'clicked' => $stats->clicked ?? 0,
                        'dropped' => $stats->dropped ?? 0,
                        'spam_complained' => $stats->spam_complained ?? 0,
                    ]);
                    $progressBar->advance();
                }
            }

            $progressBar->setMessage('done');
            $progressBar->finish();
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
