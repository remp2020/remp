<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use DateInterval;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\MailTemplateStatsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateMailTemplateStatsCommand extends Command
{
    private $logsRepository;

    private $templatesRepository;

    private $mailTemplateStatsRepository;

    public function __construct(
        LogsRepository $logsRepository,
        TemplatesRepository $templatesRepository,
        MailTemplateStatsRepository $mailTemplateStatsRepository
    ) {
        parent::__construct();
        $this->logsRepository = $logsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->mailTemplateStatsRepository = $mailTemplateStatsRepository;
    }

    protected function configure(): void
    {
        $this->setName('mail:aggregate-mail-template-stats')
            ->addArgument('date', InputArgument::OPTIONAL, 'Date which to aggregate in Y-m-d format.')
            ->setDescription('Process template stats based on batch stats and mail logs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $input->getArgument('date');

        if ($date !== null) {
            $today = (new DateTime($date))->setTime(0, 0);
        } else {
            $today = (new DateTime())->setTime(0, 0);
        }
        $yesterday = (clone $today)->sub(new DateInterval('P1D'));

        $output->writeln("Aggregating mail template stats from logs created from <info>{$yesterday->format(DATE_RFC3339)}</info> to <info>{$today->format(DATE_RFC3339)}</info>");

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
            ->where('created_at >= ?', $yesterday->format('Y-m-d'))
            ->where('created_at < ?', $today->format('Y-m-d'))
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

                $agg = $this->mailTemplateStatsRepository->byDateAndMailTemplateId($yesterday, $mailTemplateId);
                if (!$agg) {
                    $this->mailTemplateStatsRepository->insert([
                        'mail_template_id' => $mailTemplateId,
                        'date' => $yesterday->format('Y-m-d'),
                    ] + $prepData);
                } else {
                    $this->mailTemplateStatsRepository->update($agg, $prepData);
                }

                $progressBar->advance();
            }

            $progressBar->setMessage('<info>OK!</info>', 'processing');
            $progressBar->finish();
            $output->writeln('');
        } else {
            $output->writeln('<info>OK!</info> (no data)');
        }

        return Command::SUCCESS;
    }
}
