<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

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
 * compatible) property, and a `TABLE` constant naming the partitioned table
 * (used by isPartitioned()).
 */
trait MailLogsPartitioningHelpersTrait
{
    /**
     * Default --priority-threshold used by MigrateMailLogsToPartitionsCommand,
     * BackfillMailLogsPartitionsCommand and PruneMailLogsPartitionsCommand alike, so all
     * three resolve the same system/priority template set unless an operator overrides it.
     */
    public const DEFAULT_PRIORITY_THRESHOLD = 1000;

    /**
     * Column lists shared by every INSERT IGNORE ... SELECT ... used to copy rows between
     * mail_logs / mail_logs_v2 / mail_logs_old / stage_YYYY_MM tables — single source of
     * truth for the fleet-drift normalisations (see insertSelectSql()) instead of
     * copy-pasted column lists at every call site.
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
     * Secondary indexes of the canonical schema (see createCanonicalTable() in the
     * CreatePartitionedMailLogsTable migration) — single source of truth for the
     * drop-before/rebuild-after steps around a bulk load. Dropped before the backfill
     * (so rows are inserted via the near-sequential PK only, not 14 additional
     * random-insert B-trees) and rebuilt afterwards in a single combined ALTER TABLE,
     * which lets InnoDB use its sort-based bulk index builder to build every index from
     * one pass over the clustered index instead of maintaining them row-by-row during
     * the backfill (or re-scanning the table once per index).
     */
    /**
     * The p_YYYY_MM partition-name convention established by the CreatePartitionedMailLogsTable
     * migration, in PCRE form — single source of truth so every command parses/validates
     * partition names identically. Capture groups: 1 = year, 2 = month.
     */
    private const PARTITION_NAME_PATTERN = '/^p_(\d{4})_(\d{2})$/';

    /**
     * Same convention as PARTITION_NAME_PATTERN, in MySQL's POSIX regexp dialect — needed
     * wherever the match happens in SQL (e.g. filtering information_schema.PARTITIONS)
     * rather than in PHP.
     */
    private const PARTITION_NAME_SQL_PATTERN = '^p_[0-9]{4}_[0-9]{2}$';

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
     * Pins the session time_zone to the offset the running PHP process is using, so the
     * TIMESTAMP → DATETIME conversion MySQL performs while copying created_at/updated_at
     * is deterministic and matches what the app itself writes — instead of depending on
     * whichever TZ the operator's shell happens to be in.
     */
    private function pinSessionTimeZone(OutputInterface $output): void
    {
        $offset = (new \DateTime())->format('P');
        $this->database->query('SET time_zone = ?', $offset);
        $output->writeln("Session time_zone pinned to {$offset}.");
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
        return (bool) preg_match(self::PARTITION_NAME_PATTERN, $name);
    }

    /**
     * @return array{0: DateTime, 1: DateTime} [monthStart, monthEnd) for the month a
     *                                          p_YYYY_MM partition name covers
     */
    private function monthBoundsForPartitionName(string $partitionName): array
    {
        if (!preg_match(self::PARTITION_NAME_PATTERN, $partitionName, $m)) {
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
}
