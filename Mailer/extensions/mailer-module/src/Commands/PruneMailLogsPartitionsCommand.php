<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\MailLogsStatsStateRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reclaims space from the live, partitioned mail_logs table by deleting non-system/
 * non-priority rows once they age past a cutoff date — the ongoing,
 * rolling counterpart of the --cutoff-date option on mail_logs:backfill-partitions.
 *
 * Newsletter mail is only useful for a limited time; system and high-priority mail (the
 * same tiers mail_logs:migrate-to-partitions puts live, see --priority-threshold) is
 * always kept regardless of age. Rows in mail_log_conversions referencing a pruned log are
 * deleted along with it.
 *
 * Each eligible month partition is rebuilt entirely offline: build a plain staging table,
 * bulk-load only the rows to keep, build all secondary indexes in one combined ALTER,
 * then swap it into the live table with EXCHANGE PARTITION (instant, metadata-only).
 * None of the delete's index maintenance ever happens row-by-row on the live table.
 *
 * Idempotent and self-contained: whether a partition still needs pruning is decided by
 * comparing its live row count against the row count that matches the keep predicate — no
 * separate state table, and it works long after mail_logs_old has been dropped.
 *
 * Usage:
 *   php bin/command.php mail_logs:prune-partitions --cutoff-date=2025-01-01
 *   php bin/command.php mail_logs:prune-partitions --cutoff-date=2025-01-01 --limit=1
 *   php bin/command.php mail_logs:prune-partitions --cutoff-date=2025-01-01 --month=2024_07
 */
class PruneMailLogsPartitionsCommand extends Command
{
    use MailLogsPartitioningHelpersTrait;

    public const COMMAND_NAME = 'mail_logs:prune-partitions';

    public function __construct(
        private Explorer $database,
        private MailLogsStatsStateRepository $mailLogsStatsStateRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Deletes non-system/non-priority mail_logs rows (and their mail_log_conversions) older than '
                . 'a cutoff date, to reclaim space from newsletter mail that is no longer worth keeping. '
                . 'Rebuilds each affected partition offline via EXCHANGE PARTITION, same technique as '
                . 'mail_logs:backfill-partitions. Safe to run repeatedly.'
            )
            ->addOption(
                'cutoff-date',
                null,
                InputOption::VALUE_REQUIRED,
                'Required. Format YYYY-MM-DD. Rows older than this date are deleted unless they match the '
                . 'system/priority tiers (see --priority-threshold).'
            )
            ->addOption(
                'priority-threshold',
                null,
                InputOption::VALUE_REQUIRED,
                'Non-system mail types with priority strictly greater than this value are kept regardless of '
                . 'age, along with system mail. Should normally match the --priority-threshold used for '
                . 'mail_logs:migrate-to-partitions / mail_logs:backfill-partitions.',
                self::DEFAULT_PRIORITY_THRESHOLD
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum number of eligible partitions to process in this invocation. Default: all eligible partitions.'
            )
            ->addOption(
                'month',
                null,
                InputOption::VALUE_REQUIRED,
                'Process only this month (format YYYY_MM, e.g. 2024_07) instead of the normal oldest-first queue.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new TimestampedConsoleOutput(
            $output->getVerbosity(),
            $output->isDecorated(),
            $output->getFormatter(),
        );

        if (!$this->isPartitioned()) {
            $output->writeln('<error>`' . self::TABLE . '` is not a partitioned table.</error>');
            $output->writeln('<error>Run mail_logs:migrate-to-partitions first.</error>');
            return Command::FAILURE;
        }

        $cutoffDateOption = $input->getOption('cutoff-date');
        if ($cutoffDateOption === null) {
            $output->writeln('<error>--cutoff-date is required.</error>');
            return Command::FAILURE;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $cutoffDateOption)) {
            $output->writeln("<error>Invalid --cutoff-date `{$cutoffDateOption}`; expected format YYYY-MM-DD.</error>");
            return Command::FAILURE;
        }
        $cutoffDate = new DateTime($cutoffDateOption);

        $priorityThreshold = (int) $input->getOption('priority-threshold');
        $keepTemplateIds = $this->resolveSystemAndPriorityTemplateIds($priorityThreshold);

        $month = $input->getOption('month');
        $limit = $input->getOption('limit') !== null ? (int) $input->getOption('limit') : null;

        $partitions = $month !== null
            ? $this->loadSinglePartition($output, $month)
            : $this->loadPrunablePartitions($cutoffDate, $limit);

        if (!$partitions) {
            $output->writeln('<info>Nothing to prune.</info>');
            $this->mailLogsStatsStateRepository->raiseCutoffDateTo($cutoffDate);
            return Command::SUCCESS;
        }

        $this->pinSessionTimeZone($output);

        $output->writeln(sprintf(
            'Cutoff: %s. Rows before it are removed unless they match one of %d system/priority template '
            . 'id(s) (threshold > %d).',
            $cutoffDate->format('Y-m-d'),
            count($keepTemplateIds),
            $priorityThreshold
        ));
        $output->writeln('');

        foreach ($partitions as $partitionName) {
            $output->writeln("=== Pruning `{$partitionName}` ===");
            try {
                $this->prunePartition($output, $partitionName, $cutoffDate, $keepTemplateIds);
            } catch (\RuntimeException $e) {
                // Bounded lock wait exhausted, or the DDL itself was rejected (see
                // executeDdlWithBoundedLockWait(); Nette's DriverException is a RuntimeException too,
                // so both land here). Report and stop rather than moving on: the cutoff date must not
                // be raised while a partition before it still holds unpruned rows.
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                $output->writeln("<error>`{$partitionName}` was left untouched and nothing is half-applied. Resolve the "
                    . 'cause above, then re-run this command.</error>');
                return Command::FAILURE;
            }
            $output->writeln('');
        }

        $this->mailLogsStatsStateRepository->raiseCutoffDateTo($cutoffDate);

        $output->writeln('<info>Prune run complete.</info>');

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------

    /**
     * Prunes one month partition end to end: compares live vs keep-predicate row counts
     * (skipping if already pruned), builds a staging table with only the rows to keep,
     * builds its indexes offline, exchanges it into the live table, then deletes
     * mail_log_conversions rows orphaned by the removed logs. Idempotent: a leftover stage
     * table from an interrupted prior run is dropped and rebuilt from scratch.
     *
     * @param array<int, int> $keepTemplateIds
     */
    private function prunePartition(
        OutputInterface $output,
        string $partitionName,
        DateTime $cutoffDate,
        array $keepTemplateIds,
    ): void {
        [$monthStart, $monthEnd] = $this->monthBoundsForPartitionName($partitionName);
        $stageTable = $this->stageTableName($partitionName);

        [$keepClause, $keepParams] = $this->buildKeepClause(self::TABLE, $cutoffDate, $keepTemplateIds);

        $liveCount = (int) $this->database
            ->query("SELECT COUNT(*) AS cnt FROM `" . self::TABLE . "` PARTITION (`{$partitionName}`)")
            ->fetch()
            ->cnt;

        $keepCount = (int) $this->database
            ->query(
                "SELECT COUNT(*) AS cnt FROM `" . self::TABLE . "` PARTITION (`{$partitionName}`) WHERE {$keepClause}",
                ...$keepParams
            )
            ->fetch()
            ->cnt;

        if ($keepCount === $liveCount) {
            $output->writeln("  Partition already holds only kept rows ({$liveCount}); nothing to prune.");
            return;
        }

        $removedCount = $liveCount - $keepCount;
        $output->writeln("  Live partition has {$liveCount} row(s), {$keepCount} to keep, {$removedCount} to remove; building stage table …");

        $this->database->query("DROP TABLE IF EXISTS `{$stageTable}`");
        $this->database->query("CREATE TABLE `{$stageTable}` LIKE `" . self::TABLE . "`");
        $this->database->query("ALTER TABLE `{$stageTable}` REMOVE PARTITIONING");

        $output->writeln('  Dropping stage secondary indexes …');
        $this->dropSecondaryIndexes($output, $stageTable);

        $output->writeln('  Loading rows to keep …');
        $this->database->query(
            'INSERT IGNORE INTO `' . $stageTable . '`
                (' . self::INSERT_COLUMNS . ')
            SELECT
                ' . self::INSERT_COLUMNS . '
            FROM `' . self::TABLE . '`'
            . " PARTITION (`{$partitionName}`)"
            . ' WHERE `' . self::TABLE . '`.created_at >= ? AND `' . self::TABLE . '`.created_at < ?'
            . " AND {$keepClause}"
            . ' ORDER BY `' . self::TABLE . '`.id ASC',
            $monthStart,
            $monthEnd,
            ...$keepParams
        );

        $output->writeln('  Building indexes …');
        $this->rebuildSecondaryIndexes($output, $stageTable);

        // Bounded metadata-lock wait: EXCHANGE PARTITION permits concurrent DML, but acquiring
        // its lock does not — and this command runs unattended on a monthly cron, where a
        // stalled acquisition would block every mail_logs write until someone notices. See
        // executeDdlWithBoundedLockWait(). Giving up here leaves the live partition untouched
        // and only a stage table behind, which the next run drops and rebuilds.
        $output->writeln('  Exchanging partition …');
        $this->executeDdlWithBoundedLockWait(
            $output,
            "EXCHANGE PARTITION `{$partitionName}`",
            "ALTER TABLE `" . self::TABLE . "` EXCHANGE PARTITION `{$partitionName}` WITH TABLE `{$stageTable}` WITHOUT VALIDATION"
        );

        if ($this->tableExists('mail_log_conversions')) {
            $output->writeln('  Deleting orphaned mail_log_conversions …');
            [$keepClauseForDelete, $keepParamsForDelete] = $this->buildKeepClause('s', $cutoffDate, $keepTemplateIds);
            $this->database->query(
                'DELETE c FROM `mail_log_conversions` c
                 JOIN `' . $stageTable . '` s ON c.mail_log_id = s.id
                 WHERE NOT ' . $keepClauseForDelete,
                ...$keepParamsForDelete
            );
        }

        $this->database->query("DROP TABLE `{$stageTable}`");

        $output->writeln("  `{$partitionName}` pruned: removed {$removedCount} row(s).");
    }

    /**
     * @return string[] partition names eligible for pruning: p_YYYY_MM partitions whose
     *                   month starts before $cutoffDate, oldest first (the ones with the
     *                   most stale data to reclaim are pruned first).
     */
    private function loadPrunablePartitions(DateTime $cutoffDate, ?int $limit): array
    {
        $rows = $this->database->query("
            SELECT PARTITION_NAME
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND PARTITION_NAME REGEXP ?
            ORDER BY PARTITION_NAME ASC
        ", self::TABLE, self::PARTITION_NAME_PATTERN)->fetchPairs(null, 'PARTITION_NAME');

        $prunable = array_values(array_filter(
            $rows,
            function (string $partitionName) use ($cutoffDate): bool {
                [$monthStart] = $this->monthBoundsForPartitionName($partitionName);
                return $monthStart < $cutoffDate;
            }
        ));

        if ($limit !== null) {
            $prunable = array_slice($prunable, 0, max(0, $limit));
        }

        return $prunable;
    }

    /**
     * @return string[] zero or one partition name, for the --month option
     */
    private function loadSinglePartition(OutputInterface $output, string $month): array
    {
        $partitionName = $this->partitionNameForMonthOption($month);
        if ($partitionName === null) {
            $output->writeln("<error>Invalid --month `{$month}`; expected format YYYY_MM.</error>");
            return [];
        }

        $exists = (bool) $this->database->query("
            SELECT 1 FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND PARTITION_NAME = ?
        ", self::TABLE, $partitionName)->fetch();

        if (!$exists) {
            $output->writeln("<error>`{$partitionName}` does not exist on `" . self::TABLE . "`.</error>");
            return [];
        }

        return [$partitionName];
    }

    private function stageTableName(string $partitionName): string
    {
        // p_2024_07 -> stage_prune_2024_07
        return 'stage_prune_' . $this->monthSuffixForPartitionName($partitionName);
    }
}
