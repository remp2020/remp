<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\EnvironmentConfig;
use Remp\MailerModule\Models\RedisClientFactory;
use Remp\MailerModule\Models\RedisClientTrait;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\MailLogsStatsStateRepository;
use Remp\MailerModule\Repositories\MailTemplateDirectStatsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Go-live step of the mail_logs partitioning migration: migrates only what must be
 * queryable immediately — system/high-priority mail (any age) plus the complete
 * current and previous month (guaranteeing at least the last full calendar month of
 * data is live, regardless of which day of the month go-live happens to run) — into
 * the partitioned mail_logs_partitioned shadow table, then swaps the tables atomically.
 * Historical newsletter months are intentionally left out and filled in afterwards by
 * BackfillMailLogsPartitionsCommand via EXCHANGE PARTITION, which keeps their (much
 * larger) secondary-index maintenance entirely offline instead of paying
 * live-index-maintenance cost on the swapped-in table.
 *
 * Run AFTER the Phinx migration CreatePartitionedMailLogsTable has been applied
 * (i.e. mail_logs_partitioned already exists with the correct partitioned schema).
 *
 * The migration is resumable: re-run the command if it is interrupted.  It uses
 * a Redis flag to activate the dual-write path in LogsRepository, so new inserts
 * and webhook updates are mirrored into mail_logs_partitioned throughout the backfill.
 *
 * After completion:
 *   mail_logs      → partitioned; contains system/priority mail (all history) and
 *                    the current and previous month complete. Historical newsletter
 *                    months are still pending (see mail_logs_backfill_state).
 *   mail_logs_old  → original unpartitioned table; frozen source for
 *                    BackfillMailLogsPartitionsCommand. Do not drop it until that
 *                    command reports every partition done.
 *
 * Drop mail_logs_old in a follow-up migration once the backfill is complete and
 * you are satisfied with the new table (use BigintMigrationCleanupCommand or a
 * dedicated migration).
 */
class MigrateMailLogsToPartitionsCommand extends Command
{
    use RedisClientTrait;
    use MailLogsPartitioningHelpersTrait;

    public const MAIL_LOGS_PARTITIONS_MIGRATION_IS_RUNNING = 'mail_logs_partitions_migration_running';

    public const COMMAND_NAME = 'mail_logs:migrate-to-partitions';

    private const PAGE_SIZE = 200_000;

    /**
     * Per-tier Redis cursors for the Phase 1 backfill below. Explicit per-tier state
     * (rather than deriving a single high-water mark from MAX(id) on the shadow table)
     * is required because the system/priority tiers pre-insert high, recent ids ahead of
     * the recent-months tier — MAX(id) would then falsely suggest the recent-months tier
     * is nearly done on a resumed run.
     */
    private const REDIS_TIER_SYSTEM_LAST_ID = 'mail_logs_partitions_migration_tier_system_last_id';
    private const REDIS_TIER_PRIORITY_LAST_ID = 'mail_logs_partitions_migration_tier_priority_last_id';
    private const REDIS_TIER_RECENT_MONTHS_LAST_ID = 'mail_logs_partitions_migration_tier_recent_months_last_id';

    /**
     * Drives BackfillMailLogsPartitionsCommand. Seeded with one 'pending' row per
     * historical month partition below (Phase 4.5); the reserved `__swap__` row
     * records when the swap happened, for that command's update-reconciliation step.
     */
    public const BACKFILL_STATE_TABLE = 'mail_logs_backfill_state';

    public function __construct(
        private Explorer $database,
        private LogsRepository $logsRepository,
        private EnvironmentConfig $environmentConfig,
        private MailLogsStatsStateRepository $mailLogsStatsStateRepository,
        private MailTemplateDirectStatsRepository $mailTemplateDirectStatsRepository,
        RedisClientFactory $redisClientFactory,
    ) {
        parent::__construct();
        $this->redisClientFactory = $redisClientFactory;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Migrates priority mail_logs data into the partitioned table.'
                . 'Non-priority data are backfilled afterwards by mail_logs:backfill-partitions. '
                . 'Requires the CreatePartitionedMailLogsTable Phinx migration to have been applied first.'
            )
            ->addOption(
                name: 'priority-threshold',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'Non-system mail types with priority strictly greater than this value are migrated '
                . 'live at go-live time, along with system mail. Inspect the distribution first with: '
                . 'SELECT priority, COUNT(*) FROM mail_types GROUP BY priority ORDER BY priority DESC.',
                default: self::DEFAULT_PRIORITY_THRESHOLD
            )
            ->addOption(
                name: 'force',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Proceed even if mail_template_direct_stats has not been backfilled for the '
                . 'pre-migration history. Only use this once you have deliberately decided pre-migration '
                . 'direct-send statistics can be lost (e.g. a fresh install with no history worth keeping).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new TimestampedConsoleOutput(
            verbosity: $output->getVerbosity(),
            decorated: $output->isDecorated(),
            formatter: $output->getFormatter(),
        );

        $priorityThreshold = (int) $input->getOption('priority-threshold');

        $sourceTable = $this->logsRepository->getTable()->getName();  // mail_logs
        $shadowTable = $this->logsRepository->getNewTable()->getName(); // mail_logs_partitioned

        if (!$this->tableExists($shadowTable)) {
            $output->writeln("<error>Shadow table `{$shadowTable}` does not exist.</error>");
            $output->writeln('<error>Run the Phinx migration CreatePartitionedMailLogsTable first.</error>');
            return Command::FAILURE;
        }

        // Preflight: Phase 5 below renames the source table to `<source>_old`, which fails if
        // that name is taken — and it fails AFTER the point of no return (indexes dropped and
        // rebuilt, the conversions FK dropped, the dual-write flag cleared). A leftover
        // `mail_logs_old`/`mail_logs_v2` means the older bigint migration was never cleaned up;
        // its command was removed in 5.2.0, so finish it on a pre-5.2.0 release and archive the
        // leftovers with `mail:bigint_migration_cleanup mail_logs` before running this.
        foreach (["{$sourceTable}_old", "{$sourceTable}_v2"] as $leftoverTable) {
            if ($this->tableExists($leftoverTable)) {
                $output->writeln("<error>Table `{$leftoverTable}` already exists.</error>");
                $output->writeln(
                    "<error>This command renames `{$sourceTable}` to `{$sourceTable}_old`, so that name must be free, "
                    . 'and a leftover table means an unfinished bigint migration. Archive and drop the leftovers '
                    . '(`mail:bigint_migration_cleanup ' . $sourceTable . '`) before running this.</error>'
                );
                return Command::FAILURE;
            }
        }

        if (!$this->tableExists(self::BACKFILL_STATE_TABLE)) {
            $output->writeln('<error>`' . self::BACKFILL_STATE_TABLE . '` does not exist.</error>');
            $output->writeln('<error>Run the CreateMailLogsBackfillStateTable Phinx migration first.</error>');
            return Command::FAILURE;
        }

        // Preflight: mail_template_direct_stats starts out empty (see
        // CreateMailTemplateDirectStatsTable) and nothing else in this migration fills it.
        // sumForTemplates() is an unbounded lifetime SUM, so any pre-migration day left
        // unaggregated reports 0 forever for every direct-send template (in particular,
        // system/transactional templates, which have no mail_job_batch_templates rows at
        // all). Once this command swaps the tables, the current/previous month is still
        // complete, but everything older only holds system/priority mail until the backfill
        // command lands it — aggregating after that point would compute understated numbers
        // (see mail:aggregate-mail-template-stats' own stats-cutoff clamp). So this has to be
        // caught here, before anything below is touched.
        $force = (bool) $input->getOption('force');
        $mailLogsIsEmpty = $this->database->query("SELECT 1 FROM `{$sourceTable}` LIMIT 1")->fetch() === null;
        if (!$mailLogsIsEmpty) {
            $liveWindowStartForGuard = (new DateTime())->modify('first day of last month')->setTime(0, 0, 0);
            if (!$this->mailTemplateDirectStatsRepository->hasRowsBefore($liveWindowStartForGuard)) {
                $message = "`mail_template_direct_stats` has no rows before {$liveWindowStartForGuard->format('Y-m-d')} — "
                    . 'it looks like the pre-migration statistics backfill has not run yet. Once this command '
                    . 'swaps the tables, historical months are no longer complete in `mail_logs` until '
                    . 'mail_logs:backfill-partitions finishes, so pre-migration direct-send statistics would be '
                    . 'permanently stuck at 0. Run this first: '
                    . 'mail:aggregate-mail-template-stats --from=<MIN(created_at) of mail_logs>';
                if (!$force) {
                    $output->writeln("<error>{$message}</error>");
                    $output->writeln('<error>Re-run with --force once you have deliberately decided to accept this.</error>');
                    return Command::FAILURE;
                }
                $output->writeln("<comment>WARNING: {$message}</comment>");
                $output->writeln('<comment>Proceeding anyway because --force was given.</comment>');
            }
        }

        $output->writeln("STARTING mail_logs → partitioned `{$shadowTable}` MIGRATION");
        $output->writeln('');

        $this->pinSessionTimeZone($output);

        // Record (or restore) the start time so that fixTableDifferences knows which
        // updated_at range to catch up on restarts.
        $migrationStartTime = new DateTime();
        if ($this->redis()->exists(self::MAIL_LOGS_PARTITIONS_MIGRATION_IS_RUNNING)) {
            $migrationStartTime = new DateTime($this->redis()->get(self::MAIL_LOGS_PARTITIONS_MIGRATION_IS_RUNNING));
        } else {
            $this->redis()->set(self::MAIL_LOGS_PARTITIONS_MIGRATION_IS_RUNNING, $migrationStartTime->format(DATE_ATOM));
        }

        $this->database->query("SET FOREIGN_KEY_CHECKS=0; SET UNIQUE_CHECKS=0;");

        // ------------------------------------------------------------------
        // Phase 0: drop secondary indexes on the shadow table so the backfill
        // below only has to maintain the (near-sequential) primary key.
        // ------------------------------------------------------------------
        $output->writeln('Phase 0: dropping secondary indexes on shadow table …');
        $this->dropSecondaryIndexes($output, $shadowTable);
        $output->writeln('Phase 0 complete.');
        $output->writeln('');

        // ------------------------------------------------------------------
        // Phase 1: tiered paged INSERT IGNORE backfill
        //
        // Only three tiers are loaded before the swap: system/system-optional mail
        // (any age), high-priority mail (any age), and the complete current and
        // previous month (so the hot partitions never need a live-indexed backfill,
        // and at least the last full calendar month is guaranteed live regardless of
        // which day of the month go-live runs on). Everything else (historical
        // newsletters) is left for BackfillMailLogsPartitionsCommand, which loads them
        // offline per-month and EXCHANGE PARTITIONs them in — that keeps their
        // secondary-index maintenance off the live table entirely.
        // ------------------------------------------------------------------
        $output->writeln('Phase 1: backfilling data (paged INSERT IGNORE, tiered) …');

        $liveWindowStart = (clone $migrationStartTime)->modify('first day of last month')->setTime(0, 0, 0);
        $maxId = (int) ($this->database
            ->query("SELECT id FROM `{$sourceTable}` WHERE created_at <= ? ORDER BY id DESC LIMIT 1", $migrationStartTime)
            ->fetch()
            ?->id ?? 0);

        if ($maxId !== 0) {
            $systemTemplateIds = $this->resolveSystemTemplateIds();
            $priorityTemplateIds = $this->resolvePriorityTemplateIds($systemTemplateIds, $priorityThreshold);

            $liveWindowStartRow = $this->database
                ->query("SELECT id FROM `{$sourceTable}` WHERE created_at >= ? ORDER BY id ASC LIMIT 1", $liveWindowStart)
                ->fetch();
            // No rows yet in the live window → tier is empty; start cursor at maxId so
            // the loop below is a no-op.
            $liveWindowDefaultCursor = $liveWindowStartRow !== null
                ? max(0, ((int) $liveWindowStartRow->id) - 1)
                : $maxId;

            $output->writeln(sprintf(
                'Phase 1: Tiers resolved: %d system template(s), %d priority template(s) (threshold > %d), '
                . 'recent months (current + previous) starting %s.',
                count($systemTemplateIds),
                count($priorityTemplateIds),
                $priorityThreshold,
                $liveWindowStart->format('Y-m-d')
            ));

            if ($systemTemplateIds) {
                $this->backfillTier(
                    output: $output,
                    label: 'system',
                    sourceTable: $sourceTable,
                    shadowTable: $shadowTable,
                    redisCursorKey: self::REDIS_TIER_SYSTEM_LAST_ID,
                    startId: 0,
                    maxId: $maxId,
                    rangeEnd: $migrationStartTime,
                    extraWhereCondition: 'mail_template_id IN ?',
                    extraWhereParams: [$systemTemplateIds]
                );
            } else {
                $output->writeln('  Tier <comment>system</comment>: no matching mail templates, skipping.');
            }

            if ($priorityTemplateIds) {
                $this->backfillTier(
                    output: $output,
                    label: 'priority',
                    sourceTable: $sourceTable,
                    shadowTable: $shadowTable,
                    redisCursorKey: self::REDIS_TIER_PRIORITY_LAST_ID,
                    startId: 0,
                    maxId: $maxId,
                    rangeEnd: $migrationStartTime,
                    extraWhereCondition: 'mail_template_id IN ?',
                    extraWhereParams: [$priorityTemplateIds]
                );
            } else {
                $output->writeln('  Tier <comment>priority</comment>: no matching mail templates, skipping.');
            }

            $this->backfillTier(
                output: $output,
                label: 'recent-months',
                sourceTable: $sourceTable,
                shadowTable: $shadowTable,
                redisCursorKey: self::REDIS_TIER_RECENT_MONTHS_LAST_ID,
                startId: $liveWindowDefaultCursor,
                maxId: $maxId,
                rangeEnd: $migrationStartTime,
                extraWhereCondition: null,
                extraWhereParams: []
            );
        }

        $output->writeln('Phase 1 complete.');
        $output->writeln('');

        // ------------------------------------------------------------------
        // Phase 1.5: rebuild secondary indexes dropped in Phase 0, in a single
        // combined ALTER TABLE so InnoDB's sort-based bulk index builder reads
        // the clustered index once for all of them. Idempotent: a resumed run
        // only rebuilds whichever indexes are still missing.
        // Must happen before Phase 2, whose safety-net queries rely on these
        // indexes to be efficient.
        // ------------------------------------------------------------------
        $output->writeln('Phase 1.5: rebuilding secondary indexes …');
        $this->rebuildSecondaryIndexes($output, $shadowTable);
        $output->writeln('Phase 1.5 complete.');
        $output->writeln('');

        // ------------------------------------------------------------------
        // Phase 2: catch up rows updated/inserted since migration started
        // ------------------------------------------------------------------
        $output->writeln('Phase 2: applying differences (updated rows, missed inserts) …');
        $this->fixTableDifferences($sourceTable, $shadowTable, $migrationStartTime);
        $output->writeln('Phase 2 complete.');
        $output->writeln('');

        // ------------------------------------------------------------------
        // Phase 3: set AUTO_INCREMENT on the shadow table
        // ------------------------------------------------------------------
        $output->writeln('Phase 3: aligning AUTO_INCREMENT …');
        $dbName = $this->environmentConfig->get('DB_NAME');
        $this->database->query("
            SELECT MAX(id) + 10000 INTO @AutoInc FROM `{$sourceTable}`;
            SET @s := CONCAT('ALTER TABLE `{$dbName}`.`{$shadowTable}` AUTO_INCREMENT=', @AutoInc);
            PREPARE stmt FROM @s;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        $output->writeln('Phase 3 complete.');
        $output->writeln('');

        // ------------------------------------------------------------------
        // Phase 4: drop the FK from mail_log_conversions so RENAME succeeds
        // (InnoDB forbids any table from referencing a partitioned table)
        // ------------------------------------------------------------------
        $output->writeln('Phase 4: dropping mail_log_conversions → mail_logs FK …');
        $this->dropConversionsForeignKey($output);
        $output->writeln('Phase 4 complete.');
        $output->writeln('');

        // ------------------------------------------------------------------
        // Phase 4.5: seed the backfill state so
        // BackfillMailLogsPartitionsCommand knows which historical months still
        // need to be loaded. Idempotent (INSERT IGNORE) — safe to re-run.
        // ------------------------------------------------------------------
        $output->writeln('Phase 4.5: seeding backfill state …');
        $this->seedBackfillState($output, $sourceTable, $liveWindowStart);
        $output->writeln('Phase 4.5 complete.');
        $output->writeln('');

        // ------------------------------------------------------------------
        // Stop dual-writes BEFORE the swap.
        //
        // After the RENAME below, the shadow table `mail_logs_partitioned` no longer
        // exists, but LogsRepository::insert()/update() mirror writes into
        // getNewTable() (mail_logs_partitioned) while this flag is set — which would throw
        // on every live insert/webhook update. Clearing it now means writes go
        // only to the (still-live) source table until the RENAME; anything written
        // in that brief pre-rename window is reconciled by the Phase 6 catch-up
        // from the old table.
        // ------------------------------------------------------------------
        $output->writeln('Clearing dual-write flag before swap …');
        $this->redis()->del(self::MAIL_LOGS_PARTITIONS_MIGRATION_IS_RUNNING);

        // ------------------------------------------------------------------
        // Phase 5: atomic table swap
        // ------------------------------------------------------------------
        $output->writeln('Phase 5: analysing and swapping tables …');
        $this->database->query("ANALYZE TABLE `{$shadowTable}`;");
        $this->database->query("
            RENAME TABLE
                `{$sourceTable}` TO `{$sourceTable}_old`,
                `{$shadowTable}` TO `{$sourceTable}`
        ");
        $this->recordSwapTime();

        // Seeds the cutoff date (see MailLogsStatsStateRepository) to the start of the
        // live window established above — everything older is still `pending` backfill
        // (Phase 4.5), so it is correctly sealed until BackfillMailLogsPartitionsCommand
        // lowers the cutoff date as each month actually lands. No-op if already initialized
        // (e.g. a resumed/re-run migration).
        $this->mailLogsStatsStateRepository->initCutoffDate($liveWindowStart);

        $output->writeln('Phase 5 complete.');
        $output->writeln('');

        // ------------------------------------------------------------------
        // Phase 6: final catch-up from the old table
        // ------------------------------------------------------------------
        $output->writeln('Phase 6: final differences catch-up (old → new) …');
        $this->fixTableDifferences($sourceTable . '_old', $sourceTable, $migrationStartTime);
        $output->writeln('Phase 6 complete.');
        $output->writeln('');

        $this->database->query("SET FOREIGN_KEY_CHECKS=1; SET UNIQUE_CHECKS=1;");

        $output->writeln('');
        $output->writeln('<info>Go-live migration completed successfully.</info>');
        $output->writeln("<info>`{$sourceTable}` is now the partitioned table, live with system/priority mail (all history) and the complete current and previous month.</info>");
        $output->writeln("<info>`{$sourceTable}_old` is kept as the frozen source for the newsletter backfill — do NOT drop it yet.</info>");
        $output->writeln('');
        $output->writeln('<comment>IMPORTANT: clear the application cache on all web/worker nodes now.</comment>');
        $output->writeln('<comment>The swapped-in table has a different column list (no hard_bounced_at), charset</comment>');
        $output->writeln('<comment>and primary key than the one Nette cached in its database structure. Until that</comment>');
        $output->writeln('<comment>cache is rebuilt, queries are still built against the old shape and can fail with</comment>');
        $output->writeln('<comment>"Unknown column". Clear the app cache (and opcache) as part of the deploy.</comment>');
        $output->writeln('');
        $output->writeln('<comment>NEXT STEP: run mail_logs:backfill-partitions (repeatedly, or with --limit) to fill in</comment>');
        $output->writeln('<comment>historical newsletter months via EXCHANGE PARTITION. Drop `' . $sourceTable . '_old` with</comment>');
        $output->writeln('<comment>BigintMigrationCleanupCommand only once every partition is reported done.</comment>');

        return Command::SUCCESS;
    }

    /**
     * Runs one tiered paged INSERT IGNORE backfill from $sourceTable into $shadowTable,
     * covering ids in ($startId, $maxId] and (optionally) an extra WHERE condition, with
     * its own Redis cursor for resumability. Mirrors the pagination shape of the original
     * single-pass Phase 1 loop, parameterised per tier.
     *
     * $output drives the ProgressBar below too: ProgressBar's constructor swaps any
     * ConsoleOutputInterface (which TimestampedConsoleOutput is, via ConsoleOutput) for
     * its own plain, undecorated stderr stream via getErrorOutput() — so the bar never
     * actually writes through our timestamp-prefixing doWrite() override regardless of
     * which output instance it's given. The bar carries its own timestamp instead (see
     * %current_time% in the format definition).
     *
     * $rangeEnd is the date-space upper bound the progress bar measures against (the
     * migration's "now") — see the date-space comment in the method body.
     *
     * @param mixed[] $extraWhereParams
     */
    private function backfillTier(
        OutputInterface $output,
        string $label,
        string $sourceTable,
        string $shadowTable,
        string $redisCursorKey,
        int $startId,
        int $maxId,
        DateTime $rangeEnd,
        ?string $extraWhereCondition,
        array $extraWhereParams,
    ): void {
        $lastId = $this->redis()->exists($redisCursorKey)
            ? (int) $this->redis()->get($redisCursorKey)
            : $startId;

        if ($lastId >= $maxId) {
            $output->writeln("  Tier <comment>{$label}</comment>: already complete.");
            return;
        }

        $whereSuffix = $extraWhereCondition !== null ? " AND ({$extraWhereCondition})" : '';

        // Progress is tracked in date-space (created_at), not id-space: the system/priority
        // tiers scan the full id range but only insert sparse, recent matches, so the first
        // matching id can already sit near $maxId — an id-based bar would jump straight to
        // ~97% and crawl the rest from there. Measuring the span between this tier's
        // earliest matching created_at and $rangeEnd (the migration's "now") instead gives a
        // bar — and a "N day(s) of data remaining" message — that means something regardless
        // of how sparse the tier's matches are.
        ProgressBar::setPlaceholderFormatterDefinition(
            'current_time',
            static fn (): string => (new \DateTime())->format('Y-m-d H:i:s')
        );
        ProgressBar::setFormatDefinition(
            'mailLogsBackfill',
            '[%current_time%]   %message% [%bar%] %percent%% %elapsed% (ETA %remaining%)'
        );

        // Resolved from the tier's original $startId (not the resumed $lastId) so the
        // denominator — and therefore the percentage — stays stable across resumed runs.
        $minDateRow = $this->database->query(
            "SELECT created_at FROM `{$sourceTable}` WHERE id > ? AND id <= ?{$whereSuffix} ORDER BY id ASC LIMIT 1",
            $startId,
            $maxId,
            ...$extraWhereParams
        )->fetch();

        $rangeEndTs = $rangeEnd->getTimestamp();
        $rangeStartTs = $minDateRow !== null && $minDateRow->created_at !== null
            ? (new DateTime((string) $minDateRow->created_at))->getTimestamp()
            : $rangeEndTs - 1; // no matching rows at all — trivial range, loop below ends after one no-op page
        $rangeEndTs = max($rangeStartTs + 1, $rangeEndTs);
        $total = $rangeEndTs - $rangeStartTs;

        // On a fresh start the cursor date is the tier's min date; on a resumed run, look
        // up created_at of the row the Redis cursor left off at.
        $cursorTs = $lastId === $startId
            ? $rangeStartTs
            : $this->createdAtTimestamp($sourceTable, $lastId);

        $progressBar = new ProgressBar($output, $total);
        $progressBar->setFormat('mailLogsBackfill');
        $progressBar->setMessage($this->backfillProgressMessage($label, $cursorTs, $rangeEndTs));
        $progressBar->start();
        $progressBar->setProgress(min($total, max(0, $cursorTs - $rangeStartTs)));

        while ($lastId < $maxId) {
            $this->database->query(
                $this->insertSelectSql($shadowTable, $sourceTable)
                . " WHERE `{$sourceTable}`.id > ? AND `{$sourceTable}`.id <= ?{$whereSuffix}"
                . ' ORDER BY `' . $sourceTable . '`.id ASC LIMIT ' . self::PAGE_SIZE,
                $lastId,
                $maxId,
                ...$extraWhereParams
            );

            // Id and created_at of the last matching source row in the page just inserted,
            // queried from the source (not the shadow table) so it isn't affected by
            // concurrent dual-write mirrored inserts. If fewer than PAGE_SIZE rows matched,
            // this is the final page for this tier — fall back to $maxId/$rangeEnd so the
            // loop terminates and the bar lands at 100% / 0 days remaining.
            $pageLastRow = $this->database
                ->query(
                    "SELECT id, created_at FROM `{$sourceTable}` WHERE id > ? AND id <= ?{$whereSuffix} ORDER BY id ASC LIMIT "
                    . (self::PAGE_SIZE - 1) . ', 1',
                    $lastId,
                    $maxId,
                    ...$extraWhereParams
                )
                ->fetch();

            if ($pageLastRow !== null && (int) $pageLastRow->id > 0) {
                $lastId = (int) $pageLastRow->id;
                $cursorTs = (new DateTime((string) $pageLastRow->created_at))->getTimestamp();
            } else {
                $lastId = $maxId;
                $cursorTs = $rangeEndTs;
            }

            $this->redis()->set($redisCursorKey, (string) $lastId);

            $progressBar->setMessage($this->backfillProgressMessage($label, $cursorTs, $rangeEndTs));
            $progressBar->setProgress(min($total, max(0, $cursorTs - $rangeStartTs)));
        }

        $progressBar->finish();
        $output->writeln('');
    }

    private function createdAtTimestamp(string $sourceTable, int $id): int
    {
        $row = $this->database->query("SELECT created_at FROM `{$sourceTable}` WHERE id = ? LIMIT 1", $id)->fetch();
        return (new DateTime((string) $row->created_at))->getTimestamp();
    }

    private function backfillProgressMessage(string $label, int $cursorTs, int $rangeEndTs): string
    {
        $daysRemaining = (int) max(0, ceil(($rangeEndTs - $cursorTs) / 86400));
        $through = (new DateTime())->setTimestamp($cursorTs)->format('Y-m-d');

        return sprintf('Tier <comment>%s</comment>: ~%d day(s) of data remaining (through %s)', $label, $daysRemaining, $through);
    }

    /**
     * Seeds one 'pending' row per historical month (from the earliest mail_logs data up
     * to, but excluding, $liveWindowStart) into mail_logs_backfill_state, for
     * BackfillMailLogsPartitionsCommand to consume. INSERT IGNORE keeps this idempotent
     * across resumed runs.
     */
    private function seedBackfillState(OutputInterface $output, string $sourceTable, DateTime $liveWindowStart): void
    {
        $row = $this->database->query("SELECT MIN(created_at) AS min_date FROM `{$sourceTable}`")->fetch();
        if ($row === null || $row->min_date === null) {
            $output->writeln('  No historical data found; nothing to seed.');
            return;
        }

        $current = (new DateTime((string) $row->min_date))->modify('first day of this month')->setTime(0, 0, 0);

        $seeded = 0;
        while ($current < $liveWindowStart) {
            $this->database->query(
                'INSERT IGNORE INTO `' . self::BACKFILL_STATE_TABLE . '`
                    (`partition_name`, `status`, `created_at`, `updated_at`)
                 VALUES (?, \'pending\', NOW(), NOW())',
                $this->partitionNameForMonth($current)
            );
            $seeded++;
            $current->modify('+1 month');
        }

        $output->writeln("  Seeded {$seeded} historical month partition(s) as pending.");
    }

    /**
     * Records the go-live swap time in the reserved `__swap__` row of
     * mail_logs_backfill_state, so BackfillMailLogsPartitionsCommand can reconcile any
     * live updates to system rows that land in an already-frozen historical partition
     * after the swap but before that partition is exchanged in.
     */
    private function recordSwapTime(): void
    {
        $this->database->query(
            'INSERT INTO `' . self::BACKFILL_STATE_TABLE . '`
                (`partition_name`, `status`, `exchanged_at`, `created_at`, `updated_at`)
             VALUES (\'__swap__\', \'swap\', NOW(), NOW(), NOW())
             ON DUPLICATE KEY UPDATE exchanged_at = VALUES(exchanged_at), updated_at = NOW()'
        );
    }

    /**
     * Copies rows that were updated or inserted in the source table after
     * $updatedAfter into the destination table.
     */
    private function fixTableDifferences(string $from, string $to, DateTime $updatedAfter): void
    {
        // 1. Update rows that exist in both tables but diverged after migration start.
        $this->database->query("
            UPDATE `{$to}` ml_to
            JOIN   `{$from}` ml_from ON ml_to.id = ml_from.id AND ml_to.created_at = ml_from.created_at
            SET
                ml_to.email               = ml_from.email,
                ml_to.user_id             = ml_from.user_id,
                ml_to.updated_at          = COALESCE(ml_from.updated_at, ml_from.created_at),
                ml_to.subject             = ml_from.subject,
                ml_to.mail_template_id    = ml_from.mail_template_id,
                ml_to.mail_job_id         = ml_from.mail_job_id,
                ml_to.mail_job_batch_id   = ml_from.mail_job_batch_id,
                ml_to.mail_sender_id      = ml_from.mail_sender_id,
                ml_to.context             = ml_from.context,
                ml_to.delivered_at        = ml_from.delivered_at,
                ml_to.dropped_at          = COALESCE(ml_from.dropped_at, ml_from.hard_bounced_at),
                ml_to.spam_complained_at  = ml_from.spam_complained_at,
                ml_to.clicked_at          = ml_from.clicked_at,
                ml_to.opened_at           = ml_from.opened_at,
                ml_to.attachment_size     = ml_from.attachment_size
            WHERE ml_from.updated_at > ?
              AND (ml_to.updated_at IS NULL OR ml_from.updated_at != ml_to.updated_at)
        ", $updatedAfter);

        // 2. Insert rows that exist only in the source (created after migration start).
        $missingIds = $this->database->query("
            SELECT `id` FROM `{$from}`
            WHERE created_at > ?
              AND `id` NOT IN (
                  SELECT `id` FROM `{$to}` WHERE created_at > ?
              )
        ", $updatedAfter, $updatedAfter)->fetchFields();

        if ($missingIds) {
            $this->database->query(
                $this->insertSelectSql($to, $from) . " WHERE `id` IN ?",
                $missingIds
            );
        }

        // 3. Safety net: ensure any mail_sender_id from the source is also in the
        //    destination (handles edge cases where the id-based check above could miss
        //    rows that were inserted and immediately deleted, then re-inserted).
        //    The original `id` is carried over (and INSERT IGNORE used) so the row keeps
        //    its identity — mail_log_conversions references mail_logs by id, so inserting
        //    with a fresh AUTO_INCREMENT id would break that linkage.
        $query = $this->insertSelectSql($to, $from) . "
            WHERE `{$from}`.created_at >= ?
              AND mail_sender_id IS NOT NULL
              AND mail_sender_id NOT IN (
                  SELECT mail_sender_id FROM `{$to}`
                  WHERE `{$to}`.created_at >= ?
              )
        ";
        $this->database->query(
            $query,
            $updatedAfter,
            $updatedAfter,
        );
    }

    private function dropConversionsForeignKey(OutputInterface $output): void
    {
        $row = $this->database->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'mail_log_conversions'
              AND COLUMN_NAME  = 'mail_log_id'
              AND REFERENCED_TABLE_NAME = 'mail_logs'
            LIMIT 1
        ")->fetch();

        if (!$row || !$row->CONSTRAINT_NAME) {
            $output->writeln('  No FK found on mail_log_conversions (already dropped or never existed).');
            return;
        }

        $fkName = $row->CONSTRAINT_NAME;
        $output->writeln("  Dropping FK `{$fkName}` …");
        $this->database->query("ALTER TABLE mail_log_conversions DROP FOREIGN KEY `{$fkName}`");
    }
}
