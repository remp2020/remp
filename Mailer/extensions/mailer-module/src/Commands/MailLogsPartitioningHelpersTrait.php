<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Database\DriverException;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shared helpers for the mail_logs partitioning commands (MigrateMailLogsToPartitionsCommand,
 * BackfillMailLogsPartitionsCommand, PruneMailLogsPartitionsCommand and
 * SeedMailLogsPartitionsCommand).
 *
 * Several of these commands copy rows between differently-shaped mail_logs tables (the
 * fleet has drifted: utf8mb3/utf8mb4, datetime/timestamp, an extra
 * hard_bounced_at column on older instances) and need to drop/rebuild the
 * same set of secondary indexes around a bulk load. Keeping the column lists,
 * index definitions and DDL helpers here means the commands can never
 * silently drift apart from each other or from the canonical schema in
 * CreatePartitionedMailLogsTable. The same reasoning applies to the p_YYYY_MM
 * partition-name convention below: every command that names, parses or derives a stage
 * table from a partition name goes through the helpers here instead of its own
 * regexp/format copy.
 *
 * Requires the consuming class to have a `private Explorer $database` (or
 * compatible) property. The partitioned table name is fixed to self::TABLE below.
 */
trait MailLogsPartitioningHelpersTrait
{
    public const TABLE = 'mail_logs';

    /**
     * Determines minimum priority outside of system emails to keep forever.
     */
    public const DEFAULT_PRIORITY_THRESHOLD = 1000;

    /**
     * Column lists shared by every INSERT IGNORE ... SELECT ... used to copy rows between
     * mail_logs / mail_logs_partitioned / mail_logs_old / stage_YYYY_MM tables.
     */
    public const INSERT_COLUMNS = "`id`, `email`, `user_id`, `subject`,
             `mail_template_id`, `mail_job_id`, `mail_job_batch_id`,
             `mail_sender_id`, `context`,
             `delivered_at`, `dropped_at`, `spam_complained_at`,
             `clicked_at`, `opened_at`,
             `attachment_size`, `created_at`, `updated_at`";

    public const SELECT_COLUMNS = "`id`, `email`, `user_id`, `subject`,
             `mail_template_id`, `mail_job_id`, `mail_job_batch_id`,
             `mail_sender_id`, `context`,
             `delivered_at`, COALESCE(`dropped_at`, `hard_bounced_at`), `spam_complained_at`,
             `clicked_at`, `opened_at`,
             `attachment_size`, `created_at`, COALESCE(`updated_at`, `created_at`)";


    /**
     * The p_YYYY_MM partition-name convention.
     */
    private const PARTITION_NAME_PATTERN = '^p_([0-9]{4})_([0-9]{2})$';

    /**
     * Secondary indexes of the new mail_logs canonical schema (see createCanonicalTable() in the
     * CreatePartitionedMailLogsTable migration). Dropped before the backfill
     * (so rows are inserted via the near-sequential PK only, not 14 additional
     * random-insert B-trees) and rebuilt afterward.
     */
    public const SECONDARY_INDEXES = [
        'email' => ['email'],
        'user_id' => ['user_id'],
        'mail_sender_id' => ['mail_sender_id'],
        'delivered_at' => ['delivered_at'],
        'dropped_at' => ['dropped_at'],
        'spam_complained_at' => ['spam_complained_at'],
        'clicked_at' => ['clicked_at'],
        'opened_at' => ['opened_at'],
        'updated_at' => ['updated_at'],
        'created_at' => ['created_at'],
        'mail_job_id' => ['mail_job_id', 'email'],
        'contexts' => ['context'],
        'mail_logs_mail_template_id' => ['mail_template_id'],
        'mail_logs_mail_job_batch_id' => ['mail_job_batch_id'],
    ];

    /**
     * Bounded metadata-lock wait for DDL that touches the LIVE mail_logs table — see
     * executeDdlWithBoundedLockWait() for why every such statement has to go through it.
     *
     * These are deliberately fixed constants rather than CLI options: the values below are
     * safe even against a fully live table, and an operator staring at a stalled migration
     * should not have to reason about a lock-timeout knob. DDL_LOCK_WAIT_TIMEOUT is the
     * session-only `lock_wait_timeout` used per attempt; DDL_MAX_ATTEMPTS ×
     * DDL_RETRY_DELAY_SECONDS is roughly how long the command keeps trying (~10 minutes)
     * before it gives up and reports failure instead of hanging.
     */
    private const DDL_LOCK_WAIT_TIMEOUT = 5;
    private const DDL_MAX_ATTEMPTS = 60;
    private const DDL_RETRY_DELAY_SECONDS = 10;

    /**
     * MySQL ER_LOCK_WAIT_TIMEOUT — the errno `lock_wait_timeout` raises when a metadata
     * lock could not be acquired in time. Shared with `innodb_lock_wait_timeout`, but the
     * statements routed through executeDdlWithBoundedLockWait() are metadata-only.
     */
    private const MYSQL_ER_LOCK_WAIT_TIMEOUT = 1205;

    /**
     * Pins the session time_zone to the *named* zone the running PHP process uses, so the
     * TIMESTAMP → DATETIME conversion MySQL performs while copying created_at/updated_at is
     * deterministic and matches what the app itself writes — instead of depending on whichever
     * TZ the operator's shell happens to be in.
     *
     * It has to be the named zone, not a fixed offset: a fixed offset applies today's DST state
     * to every row, so on a table spanning a DST boundary every row from the other side of it
     * lands an hour off the local wall-clock time the application would have written. Measured
     * on a real 3.7M-row installation migrated in August: 1.13M rows shifted by an hour, 219 of
     * them onto the wrong calendar day (wrong daily stats bucket) and 7 into the wrong month
     * partition.
     *
     * Named zones need MySQL's time-zone tables to be populated (mysql_tzinfo_to_sql), which is
     * not guaranteed, so this falls back to the fixed offset and says plainly what that costs.
     */
    private function pinSessionTimeZone(OutputInterface $output): void
    {
        $zone = date_default_timezone_get();

        try {
            $this->database->query('SET time_zone = ?', $zone);
            $output->writeln("Session time_zone pinned to {$zone}.");
            $output->writeln('');
            return;
        } catch (DriverException $e) {
            // Unknown or incorrect time zone: mysql.time_zone_name is empty on this server.
        }

        $offset = (new \DateTime())->format('P');
        $this->database->query('SET time_zone = ?', $offset);
        $output->writeln("<comment>MySQL rejected the named time zone `{$zone}` — its time-zone tables are not "
            . "populated. Falling back to the fixed offset {$offset}.</comment>");
        $output->writeln('<comment>Rows created under a different DST offset than the one in force right now will be '
            . 'copied with created_at/updated_at off by the difference (typically an hour). To avoid that, load the '
            . 'tables once (`mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql`) and re-run.</comment>');
        $output->writeln('');
    }

    /**
     * Builds "INSERT IGNORE INTO {$to} (...) SELECT ... FROM {$from}" using the shared
     * column lists, ready for the caller to append a WHERE/ORDER/LIMIT clause. INSERT IGNORE
     * (and carrying over the original `id`) is required everywhere this is used: rows must
     * keep their identity because mail_log_conversions references mail_logs by id.
     */
    private function insertSelectSql(string $to, string $from): string
    {
        return "
            INSERT IGNORE INTO `{$to}`
                (" . self::INSERT_COLUMNS . ")
            SELECT
                " . self::SELECT_COLUMNS . "
            FROM `{$from}`
        ";
    }

    /**
     * @return array<int, int> mail_templates.id => id, for templates in a 'system' category
     *                         (mail_type_categories.code = 'system') or with a 'system'/'system_optional'
     *                         mail_types.code (system_optional is a mail_types code, not a category —
     *                         confirmed via UnsubscribeInactiveUsersCommand's $omitMailTypeCodes).
     */
    private function resolveSystemTemplateIds(): array
    {
        return $this->database->query("
            SELECT mt.id
            FROM mail_templates mt
            JOIN mail_types t ON t.id = mt.mail_type_id
            LEFT JOIN mail_type_categories c ON c.id = t.mail_type_category_id
            WHERE c.code = 'system' OR t.code IN ('system', 'system_optional')
        ")->fetchPairs(null, 'id');
    }

    /**
     * @param array<int, int> $excludeTemplateIds template ids to exclude (typically the system tier,
     *                                             so a template isn't double-counted across tiers)
     * @return array<int, int> mail_templates.id => id, for templates whose mail_types.priority > $threshold
     */
    private function resolvePriorityTemplateIds(array $excludeTemplateIds, int $threshold): array
    {
        $sql = "
            SELECT mt.id
            FROM mail_templates mt
            JOIN mail_types t ON t.id = mt.mail_type_id
            WHERE t.priority > ?
        ";
        $params = [$threshold];

        if ($excludeTemplateIds) {
            $sql .= ' AND mt.id NOT IN ?';
            $params[] = $excludeTemplateIds;
        }

        return $this->database->query($sql, ...$params)->fetchPairs(null, 'id');
    }

    /**
     * Union of the system and priority tiers — the set of mail_template_id values that must
     * always be kept regardless of age, used by the cutoff-aware backfill and the prune command
     * to build their "keep" predicate. Returns [] if neither tier matches any template (callers
     * then treat every row past the cutoff as prunable/skippable).
     *
     * @return array<int, int> mail_templates.id => id
     */
    private function resolveSystemAndPriorityTemplateIds(int $threshold): array
    {
        $systemTemplateIds = $this->resolveSystemTemplateIds();
        $priorityTemplateIds = $this->resolvePriorityTemplateIds($systemTemplateIds, $threshold);

        // array_merge, not +: both are 0-indexed lists (fetchPairs(null, 'id')), so `+` would
        // keep only one side's value per overlapping numeric key instead of unioning them.
        return array_merge($systemTemplateIds, $priorityTemplateIds);
    }

    /**
     * Builds the shared "keep" predicate used by the cutoff-aware backfill and the prune
     * command: a row is kept if it's on/after $cutoffDate, OR its mail_template_id is one
     * of the system/priority ids that must always be kept regardless of age (see
     * resolveSystemAndPriorityTemplateIds()). Returns ['', []] when $cutoffDate is null —
     * no cutoff active, callers should omit the clause entirely and keep every row, exactly
     * as before this option existed.
     *
     * @param array<int, int> $keepTemplateIds
     * @return array{0: string, 1: mixed[]} [SQL fragment (already parenthesised, no leading
     *                                        AND/WHERE — empty string means "no clause"),
     *                                        bind params in the order they appear in it]
     */
    private function buildKeepClause(string $table, ?DateTime $cutoffDate, array $keepTemplateIds): array
    {
        if ($cutoffDate === null) {
            return ['', []];
        }

        $clause = "`{$table}`.created_at >= ?";
        $params = [$cutoffDate];

        if ($keepTemplateIds) {
            $clause .= " OR `{$table}`.mail_template_id IN ?";
            $params[] = $keepTemplateIds;
        }

        return ["({$clause})", $params];
    }

    /**
     * Formats a p_YYYY_MM partition name for the month $monthStart falls in — the single
     * source for the format string, so every command names a given month's partition
     * identically.
     */
    private function partitionNameForMonth(\DateTimeInterface $monthStart): string
    {
        return 'p_' . $monthStart->format('Y_m');
    }

    /**
     * Validates and builds a p_YYYY_MM partition name from a --month CLI option value
     * (bare YYYY_MM, no p_ prefix, e.g. "2024_07") — used by the backfill and prune
     * commands' --month escape hatch. Returns null on a malformed value, so callers can
     * report the same "Invalid --month" error without duplicating the regexp.
     */
    private function partitionNameForMonthOption(string $month): ?string
    {
        if (!preg_match('/^\d{4}_\d{2}$/', $month)) {
            return null;
        }

        return 'p_' . $month;
    }

    /**
     * Whether $name follows the p_YYYY_MM convention, without throwing — for callers that
     * need to skip/warn on an unrecognised name instead of failing (e.g. filtering
     * information_schema results that may include the p_max catch-all).
     */
    private function isPartitionName(string $name): bool
    {
        return (bool) preg_match('/' . self::PARTITION_NAME_PATTERN . '/', $name);
    }

    /**
     * @return array{0: DateTime, 1: DateTime} [monthStart, monthEnd) for the month a
     *                                          p_YYYY_MM partition name covers
     */
    private function monthBoundsForPartitionName(string $partitionName): array
    {
        if (!preg_match('/' . self::PARTITION_NAME_PATTERN . '/', $partitionName, $m)) {
            throw new \InvalidArgumentException("Partition name `{$partitionName}` does not match p_YYYY_MM.");
        }

        $start = new DateTime("{$m[1]}-{$m[2]}-01 00:00:00");
        $end = (clone $start)->modify('+1 month');

        return [$start, $end];
    }

    /**
     * Returns the YYYY_MM suffix of a p_YYYY_MM partition name (e.g. p_2024_07 -> 2024_07),
     * for callers deriving a stage table name from it. Validates via
     * monthBoundsForPartitionName() rather than a bare substr(), so a malformed partition
     * name fails loudly instead of silently producing a garbage stage table name.
     */
    private function monthSuffixForPartitionName(string $partitionName): string
    {
        $this->monthBoundsForPartitionName($partitionName);

        return substr($partitionName, 2);
    }

    /**
     * Whether self::TABLE currently has any partitions — i.e. whether the partitioning
     * migration has been applied. Requires the consuming class to define a `TABLE` constant.
     */
    private function isPartitioned(): bool
    {
        $row = $this->database->query("
            SELECT COUNT(*) AS cnt
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA  = DATABASE()
              AND TABLE_NAME    = ?
              AND PARTITION_NAME IS NOT NULL
        ", self::TABLE)->fetch();

        return $row && (int) $row->cnt > 0;
    }

    private function tableExists(string $table): bool
    {
        return (bool) $this->database->query(
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             LIMIT 1",
            $table
        )->fetch();
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return (bool) $this->database->query(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
             LIMIT 1",
            $table,
            $indexName
        )->fetch();
    }

    /**
     * Drops every index in self::SECONDARY_INDEXES that currently exists on $table.
     * Idempotent: indexes already absent (e.g. a resumed run) are skipped.
     */
    private function dropSecondaryIndexes(OutputInterface $output, string $table): void
    {
        foreach (array_keys(self::SECONDARY_INDEXES) as $indexName) {
            if (!$this->indexExists($table, $indexName)) {
                continue;
            }
            $output->writeln("  Dropping index `{$indexName}` …");
            $this->database->query("ALTER TABLE `{$table}` DROP KEY `{$indexName}`");
        }
    }

    /**
     * Rebuilds every index in self::SECONDARY_INDEXES that is missing on $table, in a
     * single ALTER TABLE. Idempotent and resumable: already-built indexes (e.g. from an
     * interrupted prior run) are detected and skipped, so a restart only rebuilds what is
     * still missing. One combined ALTER (rather than one per index) lets InnoDB read the
     * clustered index once and build every missing secondary index from that single pass,
     * instead of re-scanning the whole table once per index.
     */
    private function rebuildSecondaryIndexes(OutputInterface $output, string $table): void
    {
        $missing = array_filter(
            array_keys(self::SECONDARY_INDEXES),
            fn (string $indexName): bool => !$this->indexExists($table, $indexName)
        );

        if (!$missing) {
            $output->writeln('  All secondary indexes already present.');
            return;
        }

        $output->writeln('  Building ' . count($missing) . ' missing index(es) in a single ALTER TABLE: '
            . implode(', ', $missing) . ' …');

        $this->tuneSessionForBulkDdl($output);

        $clauses = array_map(
            fn (string $indexName): string => "ADD KEY `{$indexName}` (`" . implode('`, `', self::SECONDARY_INDEXES[$indexName]) . "`)",
            $missing
        );
        $this->database->query("ALTER TABLE `{$table}` " . implode(', ', $clauses));

        $output->writeln('  Done.');
    }

    /**
     * Raises session-only DDL/sort buffer limits ahead of a bulk index rebuild, so
     * InnoDB's parallel sort-based index builder (MySQL 8.0.27+) gets more threads/memory to
     * work with. Session-only because the production migration host cannot be relied on to
     * carry any particular my.cnf — each variable is set independently and failures (e.g. an
     * older MySQL that doesn't recognise innodb_ddl_threads/innodb_ddl_buffer_size) are
     * swallowed so the migration still proceeds, just without the boost.
     */
    private function tuneSessionForBulkDdl(OutputInterface $output): void
    {
        $settings = [
            'innodb_ddl_threads' => 4,
            'innodb_ddl_buffer_size' => 256 * 1024 * 1024,
            'sort_buffer_size' => 64 * 1024 * 1024,
        ];

        foreach ($settings as $variable => $value) {
            try {
                $this->database->query("SET SESSION {$variable} = ?", $value);
            } catch (\Throwable $e) {
                $output->writeln("  <comment>Could not set session {$variable} (unsupported on this MySQL version) — continuing without it.</comment>");
            }
        }
    }

    /**
     * Runs one DDL statement against the live mail_logs table with a BOUNDED metadata-lock
     * wait, retrying until it gets a window. Every statement that takes MDL on the live
     * table (RENAME TABLE, EXCHANGE PARTITION, REORGANIZE PARTITION, DROP FOREIGN KEY) must
     * go through here.
     *
     * Why this exists: `lock_wait_timeout` defaults to 31536000 seconds — a full year, and
     * not to be confused with `innodb_lock_wait_timeout`'s 50s. A partitioning DDL statement
     * that cannot get its metadata lock therefore does not fail, it hangs; and because MDL
     * is held until transaction *commit* (not statement end), a single long-running
     * transaction is enough to stall it. Worse, the pending MDL request blocks the queue
     * behind it, so every subsequent application INSERT/UPDATE on mail_logs waits too, each
     * one holding a connection — which is exactly how a metadata-only ALTER took a whole
     * production database down (max_connections exhausted, no logins possible).
     *
     * The fix is to bound each attempt to DDL_LOCK_WAIT_TIMEOUT seconds. On timeout the
     * pending request is withdrawn, so the queued application writes drain immediately
     * instead of accumulating, and we retry after a pause. No application write ever fails
     * because of this: the timeout is set on THIS session only, never globally — nothing on
     * the application's connections changes.
     *
     * On exhaustion this throws instead of continuing, so a caller never proceeds past a
     * DDL step that did not actually happen.
     *
     * @param string[] $lockedTables tables to inspect for blockers when an attempt times out
     * @throws \RuntimeException if the lock could not be acquired within DDL_MAX_ATTEMPTS; any
     *                           driver error other than a lock-wait timeout propagates unchanged
     */
    private function executeDdlWithBoundedLockWait(
        OutputInterface $output,
        string $description,
        string $sql,
        array $lockedTables = [self::TABLE],
    ): void {
        $previousTimeout = $this->applySessionLockWaitTimeout($output);

        try {
            for ($attempt = 1; $attempt <= self::DDL_MAX_ATTEMPTS; $attempt++) {
                try {
                    $this->database->query($sql);
                    if ($attempt > 1) {
                        $output->writeln("  <info>{$description}: acquired the lock on attempt {$attempt}.</info>");
                    }
                    return;
                } catch (DriverException $e) {
                    if ((int) $e->getDriverCode() !== self::MYSQL_ER_LOCK_WAIT_TIMEOUT) {
                        throw $e;
                    }

                    $output->writeln(sprintf(
                        '  <comment>%s: could not get a metadata lock within %ds (attempt %d/%d).</comment>',
                        $description,
                        self::DDL_LOCK_WAIT_TIMEOUT,
                        $attempt,
                        self::DDL_MAX_ATTEMPTS
                    ));

                    // Said once, not on every attempt. Identifying the blocking session needs
                    // performance_schema.metadata_locks, which an application database user
                    // normally cannot read — so this points at the fix an operator can always
                    // apply, and leaves the privileged diagnosis to the runbook.
                    if ($attempt === 1) {
                        $output->writeln('    Another session holds a metadata lock on ' . implode(', ', $lockedTables)
                            . '. Metadata locks are released only at transaction commit, so this clears as soon as '
                            . 'that transaction ends.');
                        $output->writeln('    Pausing the writers listed in the partitioning runbook is the reliable '
                            . 'fix; it also shows how to identify the holder if you have a privileged account.');
                    }

                    if ($attempt < self::DDL_MAX_ATTEMPTS) {
                        $output->writeln('  Waiting ' . self::DDL_RETRY_DELAY_SECONDS . 's for writers to drain, then retrying …');
                        sleep(self::DDL_RETRY_DELAY_SECONDS);
                    }
                }
            }

            throw new \RuntimeException(sprintf(
                '%s: gave up after %d attempts over ~%d minutes without acquiring a metadata lock. '
                . 'Something is holding a long-running transaction on: %s. Nothing was changed by this step. '
                . 'Pause the writers (see the partitioning runbook, "Locking behaviour") and re-run the command.',
                $description,
                self::DDL_MAX_ATTEMPTS,
                (int) round(self::DDL_MAX_ATTEMPTS * self::DDL_RETRY_DELAY_SECONDS / 60),
                implode(', ', $lockedTables)
            ));
        } finally {
            $this->restoreSessionLockWaitTimeout($previousTimeout);
        }
    }

    /**
     * Sets this session's `lock_wait_timeout` to DDL_LOCK_WAIT_TIMEOUT and returns the
     * previous value so it can be restored. Session scope only — deliberately never
     * `SET GLOBAL`, which would make application statements start failing.
     */
    private function applySessionLockWaitTimeout(OutputInterface $output): ?int
    {
        try {
            $row = $this->database->query('SELECT @@SESSION.lock_wait_timeout AS timeout')->fetch();
            $previous = $row !== null ? (int) $row->timeout : null;
            $this->database->query('SET SESSION lock_wait_timeout = ?', self::DDL_LOCK_WAIT_TIMEOUT);

            return $previous;
        } catch (\Throwable $e) {
            // Without the bounded timeout the statement would wait for up to a year, which is
            // the failure mode this whole helper exists to prevent — so this is fatal, not
            // something to swallow like the tuneSessionForBulkDdl() boosts.
            throw new \RuntimeException(
                'Could not set a bounded session lock_wait_timeout, refusing to run partitioning DDL that '
                . 'could then block indefinitely: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    private function restoreSessionLockWaitTimeout(?int $previousTimeout): void
    {
        if ($previousTimeout === null) {
            return;
        }

        try {
            $this->database->query('SET SESSION lock_wait_timeout = ?', $previousTimeout);
        } catch (\Throwable $e) {
            // Best effort: the session is about to end anyway, and a failure here must not
            // mask the exception that is potentially already propagating.
        }
    }
}
