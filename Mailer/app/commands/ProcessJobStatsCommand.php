<?php

namespace Remp\MailerModule\Commands;

use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessJobStatsCommand extends Command
{
    private $logsRepository;

    private $batchesRepository;

    private $batchTemplatesRepository;

    public function __construct(LogsRepository $logsRepository, BatchesRepository $batchesRepository, BatchTemplatesRepository $batchTemplatesRepository)
    {
        parent::__construct();
        $this->logsRepository = $logsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
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

        $batchTemplates = $this->batchTemplatesRepository->getTable()->fetchAll();
        if (!count($batchTemplates)) {
            $output->writeln('<info>Nothing to do, exiting.</info>');
            return;
        }

        ProgressBar::setFormatDefinition(
            'processStats',
            "%processing% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
        );
        $progressBar = new ProgressBar($output, count($batchTemplates));
        $progressBar->setFormat('processStats');
        $progressBar->start();

        /** @var ActiveRow $batchTemplate */
        foreach ($batchTemplates as $batchTemplate) {
            $progressBar->setMessage('Processing jobBatchTemplate <info>' . $batchTemplate->id . '</info>', 'processing');
            $stats = $this->logsRepository->getBatchTemplateStats($batchTemplate);

            $this->batchTemplatesRepository->update($batchTemplate, [
                'delivered' => $stats->delivered ?? 0,
                'opened' => $stats->opened ?? 0,
                'clicked' => $stats->clicked ?? 0,
                'converted' => $stats->converted ?? 0,
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
