<?php

namespace Remp\MailerModule\Commands;

use DateTime;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\MailTemplatesAggregatedDataRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateSystemMailTemplateStatsCommand extends Command
{
    /**
     * @var LogsRepository
     */
    private $logsRepository;

    /**
     * @var TemplatesRepository
     */
    private $templatesRepository;

    /**
     * @var MailTemplatesAggregatedDataRepository
     */
    private $mailTemplatesAggregatedDataRepository;

    public function __construct(
        LogsRepository $logsRepository,
        TemplatesRepository $templatesRepository,
        MailTemplatesAggregatedDataRepository $mailTemplatesAggregatedDataRepository
    ) {
        parent::__construct();
        $this->logsRepository = $logsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->mailTemplatesAggregatedDataRepository = $mailTemplatesAggregatedDataRepository;
    }

    protected function configure()
    {
        $this->setName('mail:aggregate-system-template-stats')
            ->addArgument('date', InputArgument::OPTIONAL, 'Date which to aggregate in Y-m-d format.')
            ->setDescription('Process template stats based on batch stats and mail logs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** AGGREGATE SYSTEM MAIL TEMPLATE STATS  *****</info>');
        $output->writeln('');

        $date = $input->getArgument('date');

        if ($date !== null) {
            $today = new DateTime($date);
            $yesterday = new DateTime($date . ' -1 day');
        } else {
            $today = new DateTime;
            $yesterday = new DateTime('-1 day');
        }

        $data = $this->logsRepository
            ->getTable()
            ->select('
                mail_template_id,
                COUNT(created_at) AS sent,
                COUNT(delivered_at) AS delivered, 
                COUNT(opened_at) AS opened,  
                COUNT(clicked_at) AS clicked, 
                COUNT(dropped_at) AS dropped, 
                COUNT(spam_complained_at) AS spam_complained 
            ')
            ->where('mail_job_batch_id IS NULL')
            ->where('DATE(created_at) >= ?', $yesterday->format('Y-m-d'))
            ->where('DATE(created_at) < ?', $today->format('Y-m-d'))
            ->group('mail_template_id')
            ->fetchAssoc('mail_template_id');

        ProgressBar::setFormatDefinition(
            'processStats',
            "%processing% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
        );

        if (count($data)) {
            $progressBar = new ProgressBar($output, count($data));
            $progressBar->setFormat('processStats');
            $progressBar->start();

            foreach ($data as $mailTemplateId => $mailTemplateData) {
                $progressBar->setMessage('Processing template <info>' . $mailTemplateId . '</info>', 'processing');

                $prepData = [
                    'sent' => $mailTemplateData['sent'],
                    'delivered' => $mailTemplateData['delivered'],
                    'opened' => $mailTemplateData['opened'],
                    'clicked' => $mailTemplateData['clicked'],
                    'dropped' => $mailTemplateData['dropped'],
                    'spam_complained' => $mailTemplateData['spam_complained'],
                ];

                $agg = $this->mailTemplatesAggregatedDataRepository->byDateAndMailTemplateId($today, $mailTemplateId);
                if (!$agg) {
                    $this->mailTemplatesAggregatedDataRepository->insert([
                        'mail_template_id' => $mailTemplateId,
                        'date' => $today->format('Y-m-d'),
                    ] + $prepData);
                } else {
                    $this->mailTemplatesAggregatedDataRepository->update($agg, $prepData);
                }

                $progressBar->advance();
            }

            $progressBar->setMessage('done');
            $progressBar->finish();
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }
}
