<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use DateInterval;
use DatePeriod;
use DateTimeInterface;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\LogConversionsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\MailLogsStatsStateRepository;
use Remp\MailerModule\Repositories\MailTemplateDirectStatsRepository;
use Remp\MailerModule\Repositories\MailTemplateStatsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateMailTemplateStatsCommand extends Command
{
    private const STATS_COLUMNS_SELECT = '
        mail_template_id,
        COUNT(created_at) AS sent,
        COUNT(delivered_at) AS delivered,
        COUNT(opened_at) AS opened,
        COUNT(clicked_at) AS clicked,
        COUNT(dropped_at) AS dropped,
        COUNT(spam_complained_at) AS spam_complained
    ';

    public function __construct(
        private readonly LogsRepository $logsRepository,
        private readonly LogConversionsRepository $logConversionsRepository,
        private readonly MailTemplateStatsRepository $mailTemplateStatsRepository,
        private readonly MailTemplateDirectStatsRepository $mailTemplateDirectStatsRepository,
        private readonly MailLogsStatsStateRepository $mailLogsStatsStateRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('mail:aggregate-mail-template-stats')
            ->addOption(
                name: 'date',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Specific date to aggregate (Y-m-d). Refused if it is older than the active '
                    . 'mail_logs_stats_state cutoff date, unless --force is given. Defaults to yesterday and '
                    . 'today if neither --date nor --from is given.',
            )
            ->addOption(
                name: 'from',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Processes every day since the specified day (Y-m-d) up to and including today. '
                    . 'Clamped to the active mail_logs_stats_state cutoff date, unless --force is given, since '
                    . 'mail_logs no longer holds complete data before it.',
            )
            ->addOption(
                name: 'force',
                mode: InputOption::VALUE_NONE,
                description: 'Bypass the mail_logs_stats_state cutoff date clamp/refusal for --from/--date. '
                    . 'Only use this if you know the requested range is still backed by complete mail_logs data '
                    . '(e.g. the pre-go-live backfill, before any cutoff date is active).',
            )
            ->setDescription(
                'Process template stats based on batch stats and mail logs. With neither --date nor --from, '
                . 'aggregates yesterday and today.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $from = $input->getOption('from');
        $date = $input->getOption('date');
        $force = (bool) $input->getOption('force');

        if ($from && $date) {
            $output->writeln('<error>ERROR:</error> Only one of --from and --date is allowed');
            return Command::FAILURE;
        }

        $cutoffDate = $this->mailLogsStatsStateRepository->getCutoffDate();

        if ($from) {
            $startDate = (new DateTime($from))->setTime(0, 0);

            if ($cutoffDate !== null && $startDate < $cutoffDate && !$force) {
                $output->writeln(sprintf(
                    '<comment>NOTE:</comment> --from=%s is older than the active stats cutoff date (%s); '
                    . 'mail_logs no longer holds complete data before it, so the range is clamped to the '
                    . 'cutoff date. Pass --force to aggregate the full range anyway.',
                    $startDate->format('Y-m-d'),
                    $cutoffDate->format('Y-m-d'),
                ));
                $startDate = (new DateTime($cutoffDate->format('Y-m-d')))->setTime(0, 0);
            }

            $endDate = (new DateTime('today'))->modify('+1 day');
            $interval = new DateInterval('P1D');
            $dateRange = new DatePeriod($startDate, $interval, $endDate);

            foreach ($dateRange as $day) {
                $this->processDay($output, DateTime::from($day));
            }
        } elseif ($date) {
            $requestedDate = (new DateTime($date))->setTime(0, 0);

            if ($cutoffDate !== null && $requestedDate < $cutoffDate && !$force) {
                $output->writeln(sprintf(
                    '<error>ERROR:</error> --date=%s is older than the active stats cutoff date (%s); '
                    . 'mail_logs no longer holds complete data for it. Pass --force to aggregate it anyway.',
                    $requestedDate->format('Y-m-d'),
                    $cutoffDate->format('Y-m-d'),
                ));
                return Command::FAILURE;
            }

            $this->processDay($output, $requestedDate);
        } else {
            // No option given: keep yesterday's rollup current (late-arriving webhook
            // events keep landing on it after it was first sealed) and refresh today's,
            // since template/job detail now reads direct-send stats exclusively from the
            // persisted rollup rather than live mail_logs.
            $this->processDay($output, new DateTime('yesterday'));
            $this->processDay($output, new DateTime('today'));
        }

        return Command::SUCCESS;
    }

    private function processDay(OutputInterface $output, DateTime $date)
    {
        $periodStart = $date->setTime(0, 0);
        $periodEnd = (clone $periodStart)->add(new DateInterval('P1D'));

        $output->writeln("Aggregating mail template stats from logs created from <info>{$periodStart->format(DATE_RFC3339)}</info> to <info>{$periodEnd->format(DATE_RFC3339)}</info>");

        $allTemplateData = $this->logsRepository
            ->getTable()
            ->select(self::STATS_COLUMNS_SELECT)
            ->where('created_at >= ?', $periodStart)
            ->where('created_at < ?', $periodEnd)
            ->group('mail_template_id')
            ->fetchPairs('mail_template_id');

        $directOnlyTemplateData = $this->logsRepository
            ->getTable()
            ->select(self::STATS_COLUMNS_SELECT)
            ->where('created_at >= ?', $periodStart)
            ->where('created_at < ?', $periodEnd)
            ->where('mail_job_id IS NULL')
            ->group('mail_template_id')
            ->fetchPairs('mail_template_id');

        $allConvertedByTemplate = $this->logConversionsRepository->countConvertedGroupedByTemplate(
            createdAtFrom: $periodStart,
            createdAtTo: $periodEnd,
        );
        $directConvertedByTemplate = $this->logConversionsRepository->countConvertedGroupedByTemplate(
            createdAtFrom: $periodStart,
            createdAtTo: $periodEnd,
            directOnly: true,
        );

        ProgressBar::setFormatDefinition(
            'processStats',
            "%processing% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
        );

        $mailTemplateIds = array_keys($allTemplateData);

        if (count($mailTemplateIds)) {
            $progressBar = new ProgressBar($output, count($mailTemplateIds));
            $progressBar->setFormat('processStats');
            $progressBar->start();

            foreach ($mailTemplateIds as $mailTemplateId) {
                $progressBar->setMessage('Processing template <info>' . $mailTemplateId . '</info>', 'processing');

                $this->mailTemplateStatsRepository->upsert(
                    date: $periodStart,
                    mailTemplateId: $mailTemplateId,
                    sent: $allTemplateData[$mailTemplateId]['sent'],
                    delivered: $allTemplateData[$mailTemplateId]['delivered'],
                    opened: $allTemplateData[$mailTemplateId]['opened'],
                    clicked: $allTemplateData[$mailTemplateId]['clicked'],
                    dropped: $allTemplateData[$mailTemplateId]['dropped'],
                    spamComplained: $allTemplateData[$mailTemplateId]['spam_complained'],
                    converted: $allConvertedByTemplate[$mailTemplateId] ?? 0,
                );

                if (isset($directOnlyTemplateData[$mailTemplateId])) {
                    $this->mailTemplateDirectStatsRepository->upsert(
                        date: $periodStart,
                        mailTemplateId: $mailTemplateId,
                        sent: $directOnlyTemplateData[$mailTemplateId]['sent'],
                        delivered: $directOnlyTemplateData[$mailTemplateId]['delivered'],
                        opened: $directOnlyTemplateData[$mailTemplateId]['opened'],
                        clicked: $directOnlyTemplateData[$mailTemplateId]['clicked'],
                        dropped: $directOnlyTemplateData[$mailTemplateId]['dropped'],
                        spamComplained: $directOnlyTemplateData[$mailTemplateId]['spam_complained'],
                        converted: $directConvertedByTemplate[$mailTemplateId] ?? 0,
                    );
                }

                $progressBar->advance();
            }

            $progressBar->setMessage('<info>OK!</info>', 'processing');
            $progressBar->finish();
            $output->writeln('');
        } else {
            $output->writeln('<info>OK!</info> (no data)');
        }
    }
}
