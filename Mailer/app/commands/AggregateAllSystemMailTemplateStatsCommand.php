<?php

namespace Remp\MailerModule\Commands;

use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Remp\MailerModule\Repository\LogsRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateAllSystemMailTemplateStatsCommand extends Command
{
    /**
     * @var LogsRepository
     */
    private $logsRepository;

    public function __construct(
        LogsRepository $logsRepository
    ) {
        parent::__construct();
        $this->logsRepository = $logsRepository;
    }

    protected function configure()
    {
        $this->setName('mail:aggregate-all-system-template-stats')
            ->setDescription('Aggregate all system template stats from mails logs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** AGGREGATE ALL SYSTEM MAIL TEMPLATE STATS *****</info>');
        $output->writeln('');

        $aggregateCommand = $this->getApplication()->find('mail:aggregate-system-template-stats');

        $date = $this->logsRepository->getTable()
            ->select('created_at')
            ->where('mail_job_batch_id IS NULL')
            ->order('created_at')
            ->fetchField('created_at');

        while ($date < new DateTime()) {
            $output->writeln('<info>AGGREGATE DATE ' . $date->format('Y-m-d') . '</info>');

            $args = new ArrayInput([
                'date' => $date->format('Y-m-d')
            ]);

            $aggregateCommand->run($args, $output);

            $date = $date->modify('+1 day');
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }
}
