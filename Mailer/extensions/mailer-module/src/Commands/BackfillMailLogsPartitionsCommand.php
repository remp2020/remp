<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Backfills historical mail_logs months into the live, partitioned mail_logs
 * table left behind by MigrateMailLogsToPartitionsCommand.
 *
 * That command only puts system/high-priority mail (any age) and the current
 * month live before swapping mail_logs into place; every older month is left
 * `pending` in mail_logs_backfill_state, with its data still sitting in the
 * frozen `mail_logs_old` snapshot. This command fills those months in, one at
 * a time, entirely offline: build a plain (non-partitioned) staging table,
 * bulk-load the month, build all secondary indexes in one combined ALTER
 * (InnoDB's sort-based bulk index builder, not row-by-row maintenance), then
 * swap it into the live table with `ALTER TABLE ... EXCHANGE PARTITION`
 *
 * Run repeatedly (e.g. from cron, or with --limit) until every partition is
 * reported done; safe to interrupt and resume at any point — leftover stage
 * tables from a killed run are dropped and rebuilt on the next invocation.
 *
 * Requires MigrateMailLogsToPartitionsCommand to have already completed.
 *
 * Usage:
 *   php bin/command.php mail_logs:backfill-partitions
 *   php bin/command.php mail_logs:backfill-partitions --limit=1
 *   php bin/command.php mail_logs:backfill-partitions --month=2024_07
 *   php bin/command.php mail_logs:backfill-partitions --cutoff-date=2025-01-01
 *
 * --cutoff-date makes months entirely older than it load only their system/priority rows
 * (the same tiers mail_logs:migrate-to-partitions already put live) instead of the
 * full mail_logs data — useful when historical records are not worth the storage.
 * Once a month is marked done under a cutoff, it stays partial: re-running without (or
 * with a wider) cutoff does not top it back up, since `mail_logs_backfill_state` only
 * tracks pending vs done, not which rows were skipped.
 */
class BackfillMailLogsPartitionsCommand extends Command
{
    use MailLogsPartitioningHelpersTrait;

    public const COMMAND_NAME = 'mail_logs:backfill-partitions';

    private const TABLE = 'mail_logs';

    private const OLD_TABLE = 'mail_logs_old';

    public function __construct(
        private Explorer $database,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Backfills historical newsletter months (left pending by mail_logs:migrate-to-partitions) '
                . 'into the live partitioned mail_logs table via EXCHANGE PARTITION. Safe to run repeatedly '
                . 'until every month is done.'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Maximum number of pending months to process in this invocation. Default: all pending months.'
            )
            ->addOption(
                'month',
                null,
                InputOption::VALUE_REQUIRED,
                'Process only this month (format YYYY_MM, e.g. 2024_07) instead of the normal newest-first queue.'
            )
            ->addOption(
                'cutoff-date',
                null,
                InputOption::VALUE_REQUIRED,
                'Format YYYY-MM-DD. When set, rows older than this date are only backfilled if they match the '
                . 'system/priority tiers (see --priority-threshold) — everything else in a pending month older '
                . 'than the cutoff is left out entirely. Without this option every row in a pending month is '
                . 'backfilled, same as before this flag existed.'
            )
            ->addOption(
                'priority-threshold',
                null,
                InputOption::VALUE_REQUIRED,
                'Used only together with --cutoff-date, to resolve the same system/priority template set as '
                . 'go-live. Should normally match the --priority-threshold used for '
                . 'mail_logs:migrate-to-partitions.',
                self::DEFAULT_PRIORITY_THRESHOLD
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

        if (!$this->tableExists(MigrateMailLogsToPartitionsCommand::BACKFILL_STATE_TABLE)) {
            $output->writeln('<error>`' . MigrateMailLogsToPartitionsCommand::BACKFILL_STATE_TABLE . '` does not exist.</error>');
            $output->writeln('<error>Run mail_logs:migrate-to-partitions first.</error>');
            return Command::FAILURE;
        }

        if (!$this->tableExists(self::OLD_TABLE)) {
            $output->writeln('<error>`' . self::OLD_TABLE . '` does not exist — nothing to backfill from.</error>');
            $output->writeln('<error>Run mail_logs:migrate-to-partitions first.</error>');
            return Command::FAILURE;
        }

        $swapTime = $this->loadSwapTime();
        if ($swapTime === null) {
            $output->writeln('<error>No go-live swap time recorded yet — mail_logs:migrate-to-partitions has not completed.</error>');
            return Command::FAILURE;
        }

        $month = $input->getOption('month');
        $limit = $input->getOption('limit') !== null ? (int) $input->getOption('limit') : null;

        $cutoffDateOption = $input->getOption('cutoff-date');
        $cutoffDate = null;
        if ($cutoffDateOption !== null) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $cutoffDateOption)) {
                $output->writeln("<error>Invalid --cutoff-date `{$cutoffDateOption}`; expected format YYYY-MM-DD.</error>");
                return Command::FAILURE;
            }
            $cutoffDate = new DateTime($cutoffDateOption);
        }

        $priorityThreshold = (int) $input->getOption('priority-threshold');
        $keepTemplateIds = $cutoffDate !== null
            ? $this->resolveSystemAndPriorityTemplateIds($priorityThreshold)
            : [];

        $partitions = $month !== null
            ? $this->loadSinglePendingPartition($output, $month)
            : $this->loadPendingPartitions($limit);

        if (!$partitions) {
            $output->writeln('<info>Nothing pending. All historical months are already backfilled.</info>');
            return Command::SUCCESS;
        }

        $this->pinSessionTimeZone($output);

        if ($cutoffDate !== null) {
            $output->writeln(sprintf(
                'Cutoff active: rows before %s are skipped unless they match one of %d system/priority '
                . 'template id(s) (threshold > %d).',
                $cutoffDate->format('Y-m-d'),
                count($keepTemplateIds),
                $priorityThreshold
            ));
            $output->writeln('');
        }

        foreach ($partitions as $partitionName) {
            $output->writeln("=== Backfilling `{$partitionName}` ===");
            $this->backfillPartition($output, $partitionName, $swapTime, $cutoffDate, $keepTemplateIds);
            $output->writeln('');
        }

        $output->writeln('<info>Backfill run complete.</info>');
        $remaining = $this->countPending();
        if ($remaining > 0) {
            $output->writeln("<comment>{$remaining} historical month(s) still pending — run this command again.</comment>");
        } else {
            $output->writeln('<info>All historical months are done. `' . self::OLD_TABLE . '` can now be dropped '
                . '(e.g. via BigintMigrationCleanupCommand or a dedicated migration).</info>');
        }

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------

    /**
     * Backfills one historical month partition end to end: builds a staging table, bulk
     * loads the month, builds its indexes offline, reconciles post-swap updates, then
     * exchanges it into the live table. Idempotent: a leftover stage table from an
     * interrupted prior run is dropped and rebuilt from scratch.
     *
     * When $cutoffDate is set, only rows on/after it, or matching $keepTemplateIds
     * (system/priority), are loaded — see buildKeepClause() in
     * MailLogsPartitioningHelpersTrait. $cutoffDate null means "no cutoff", i.e. every row
     * for the month is loaded, exactly as before this option existed.
     *
     * @param array<int, int> $keepTemplateIds
     */
    private function backfillPartition(
        OutputInterface $output,
        string $partitionName,
        DateTime $swapTime,
        ?DateTime $cutoffDate,
        array $keepTemplateIds,
    ): void {
        [$monthStart, $monthEnd] = $this->monthBoundsForPartitionName($partitionName);
        $stageTable = $this->stageTableName($partitionName);

        [$keepClause, $keepParams] = $this->buildKeepClause(self::OLD_TABLE, $cutoffDate, $keepTemplateIds);
        $keepSuffix = $keepClause !== '' ? " AND {$keepClause}" : '';

        $targetCount = (int) $this->database
            ->query(
                "SELECT COUNT(*) AS cnt FROM `" . self::OLD_TABLE . "` WHERE created_at >= ? AND created_at < ?{$keepSuffix}",
                $monthStart,
                $monthEnd,
                ...$keepParams
            )
            ->fetch()
            ->cnt;

        $liveCount = (int) $this->database
            ->query("SELECT COUNT(*) AS cnt FROM `" . self::TABLE . "` PARTITION (`{$partitionName}`)")
            ->fetch()
            ->cnt;

        if ($targetCount === $liveCount) {
            // Every row targeted for this month already made it live during go-live (e.g.
            // the month was entirely system/priority mail, or a cutoff excludes the rest of
            // it) — nothing to exchange, just mark it done.
            $output->writeln("  Live partition already has all {$liveCount} targeted row(s) for this month; skipping stage/exchange.");
            $this->markDone($partitionName);
            return;
        }

        $output->writeln("  Target has {$targetCount} row(s), live partition has {$liveCount}; building stage table …");

        $this->database->query("DROP TABLE IF EXISTS `{$stageTable}`");
        $this->database->query("CREATE TABLE `{$stageTable}` LIKE `" . self::TABLE . "`");
        $this->database->query("ALTER TABLE `{$stageTable}` REMOVE PARTITIONING");

        $output->writeln('  Dropping stage secondary indexes …');
        $this->dropSecondaryIndexes($output, $stageTable);

        $output->writeln('  Bulk loading month …');
        $this->database->query(
            $this->insertSelectSql($stageTable, self::OLD_TABLE)
            . ' WHERE `' . self::OLD_TABLE . '`.created_at >= ? AND `' . self::OLD_TABLE . '`.created_at < ?' . $keepSuffix
            . ' ORDER BY `' . self::OLD_TABLE . '`.id ASC',
            $monthStart,
            $monthEnd,
            ...$keepParams
        );

        $output->writeln('  Building indexes …');
        $this->rebuildSecondaryIndexes($output, $stageTable);

        $output->writeln('  Reconciling post-swap updates …');
        $this->reconcilePostSwapUpdates($stageTable, $partitionName, $monthStart, $monthEnd, $swapTime);

        $output->writeln('  Exchanging partition …');
        $this->database->query(
            "ALTER TABLE `" . self::TABLE . "` EXCHANGE PARTITION `{$partitionName}` WITH TABLE `{$stageTable}` WITHOUT VALIDATION"
        );

        $this->database->query("DROP TABLE `{$stageTable}`");

        $this->markDone($partitionName);
        $output->writeln("  `{$partitionName}` done.");
    }

    /**
     * Overlays any row in the live partition that changed after the go-live swap (e.g. a
     * webhook delivery event landing on a system row that happens to fall in this
     * historical month) onto the freshly-built stage table, so the EXCHANGE doesn't
     * clobber it with the frozen mail_logs_old snapshot. Cheap: only scans the one
     * live partition, and in the common case (an old month, long since delivered) matches
     * zero rows.
     */
    private function reconcilePostSwapUpdates(
        string $stageTable,
        string $partitionName,
        DateTime $monthStart,
        DateTime $monthEnd,
        DateTime $swapTime,
    ): void {
        // Deliberately a plain INSERT ... ON DUPLICATE KEY UPDATE, not the shared
        // insertSelectSql() IGNORE helper: here a PK collision (id, created_at) with the
        // frozen snapshot already loaded into the stage table is the expected, common case,
        // and must overwrite it with the live values rather than being silently ignored.
        //
        // Reads INSERT_COLUMNS (not SELECT_COLUMNS) on both sides: the source here is the
        // live, canonical `mail_logs` table, not the fleet-drifted mail_logs_old — it has no
        // hard_bounced_at column, so SELECT_COLUMNS's COALESCE(dropped_at, hard_bounced_at)
        // would fail with "Unknown column".
        $this->database->query(
            'INSERT INTO `' . $stageTable . '`
                (' . self::INSERT_COLUMNS . ')
             SELECT
                ' . self::INSERT_COLUMNS . '
             FROM `' . self::TABLE . "`
             PARTITION (`{$partitionName}`)"
            . ' WHERE `' . self::TABLE . '`.created_at >= ? AND `' . self::TABLE . '`.created_at < ?'
            . ' AND `' . self::TABLE . '`.updated_at > ?'
            . ' ON DUPLICATE KEY UPDATE
                `email` = VALUES(`email`),
                `user_id` = VALUES(`user_id`),
                `subject` = VALUES(`subject`),
                `mail_template_id` = VALUES(`mail_template_id`),
                `mail_job_id` = VALUES(`mail_job_id`),
                `mail_job_batch_id` = VALUES(`mail_job_batch_id`),
                `mail_sender_id` = VALUES(`mail_sender_id`),
                `context` = VALUES(`context`),
                `delivered_at` = VALUES(`delivered_at`),
                `dropped_at` = VALUES(`dropped_at`),
                `spam_complained_at` = VALUES(`spam_complained_at`),
                `clicked_at` = VALUES(`clicked_at`),
                `opened_at` = VALUES(`opened_at`),
                `attachment_size` = VALUES(`attachment_size`),
                `updated_at` = VALUES(`updated_at`)',
            $monthStart,
            $monthEnd,
            $swapTime
        );
    }

    /**
     * @return string[] partition names, e.g. ['p_2024_07']
     */
    private function loadPendingPartitions(?int $limit): array
    {
        $sql = "
            SELECT `partition_name`
            FROM `" . MigrateMailLogsToPartitionsCommand::BACKFILL_STATE_TABLE . "`
            WHERE `status` = 'pending'
            ORDER BY `partition_name` DESC
        ";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit);
        }

        return $this->database->query($sql)->fetchPairs(null, 'partition_name');
    }

    /**
     * @return string[] zero or one partition name, for the --month option
     */
    private function loadSinglePendingPartition(OutputInterface $output, string $month): array
    {
        $partitionName = $this->partitionNameForMonthOption($month);
        if ($partitionName === null) {
            $output->writeln("<error>Invalid --month `{$month}`; expected format YYYY_MM.</error>");
            return [];
        }

        $row = $this->database->query(
            'SELECT `status` FROM `' . MigrateMailLogsToPartitionsCommand::BACKFILL_STATE_TABLE . '` WHERE `partition_name` = ?',
            $partitionName
        )->fetch();

        if ($row === null) {
            $output->writeln("<error>`{$partitionName}` is not a known historical month (not seeded in "
                . MigrateMailLogsToPartitionsCommand::BACKFILL_STATE_TABLE . ').</error>');
            return [];
        }

        if ($row->status !== 'pending') {
            $output->writeln("<info>`{$partitionName}` is already `{$row->status}`; nothing to do.</info>");
            return [];
        }

        return [$partitionName];
    }

    private function countPending(): int
    {
        return (int) $this->database
            ->query(
                'SELECT COUNT(*) AS cnt FROM `' . MigrateMailLogsToPartitionsCommand::BACKFILL_STATE_TABLE . "` WHERE `status` = 'pending'"
            )
            ->fetch()
            ->cnt;
    }

    private function markDone(string $partitionName): void
    {
        $this->database->query(
            'UPDATE `' . MigrateMailLogsToPartitionsCommand::BACKFILL_STATE_TABLE . "`
             SET `status` = 'done', `updated_at` = NOW()
             WHERE `partition_name` = ?",
            $partitionName
        );
    }

    private function loadSwapTime(): ?DateTime
    {
        $row = $this->database->query(
            'SELECT `exchanged_at` FROM `' . MigrateMailLogsToPartitionsCommand::BACKFILL_STATE_TABLE . "` WHERE `partition_name` = '__swap__'"
        )->fetch();

        return $row?->exchanged_at;
    }

    private function stageTableName(string $partitionName): string
    {
        // p_2024_07 -> stage_2024_07
        return 'stage_' . $this->monthSuffixForPartitionName($partitionName);
    }
}
