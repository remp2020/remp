<?php

namespace Remp\MailerModule\Commands;

use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessJobStatsCommand extends Command
{
    private $logsRepository;

    private $batchesRepository;

    public function __construct(LogsRepository $logsRepository, BatchesRepository $batchesRepository)
    {
        parent::__construct();
        $this->logsRepository = $logsRepository;
        $this->batchesRepository = $batchesRepository;
    }

    protected function configure()
    {
        $this->setName('mail:job-stats')
            ->setDescription('Process job stats based on mail logs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** UPDATE EMAIL JOB STATS *****</info>');
        $output->writeln('');

        $batches = $this->batchesRepository->getTable()->fetchAll();

        ProgressBar::setFormatDefinition(
            'processStats',
            "%processing% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
        );
        $progressBar = new ProgressBar($output, count($batches));
        $progressBar->setFormat('processStats');
        $progressBar->start();

        /** @var ActiveRow $batch */
        foreach ($batches as $batch) {
            $progressBar->setMessage('Processing batch <info>' . $batch->id . '</info>', 'processing');
            $stats = $this->logsRepository->getBatchStats($batch);

            $this->batchesRepository->update($batch, [
                'delivered' => $stats->delivered ?? 0,
                'opened' => $stats->opened ?? 0,
                'clicked' => $stats->clicked ?? 0,
                'dropped' => $stats->dropped ?? 0,
                'spam_complained' => $stats->spam_complained ?? 0,
                'hard_bounced' => $stats->hard_bounced ?? 0,
            ]);
            $progressBar->advance();
        }

        $progressBar->setMessage('done');
        $progressBar->finish();

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }
}