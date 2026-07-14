<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePartitionedMailLogsTable extends AbstractMigration
{
    private const SHADOW_TABLE = 'mail_logs_partitioned';

    public function up(): void
    {
        $this->assertBigintMigrationCompleted();

        if ($this->hasTable(self::SHADOW_TABLE)) {
            // Shadow table already exists from a previous partial run; skip creation.
            return;
        }

        $partitionsSql = $this->buildPartitionDefinitions();

        if ($this->tableIsEmpty('mail_logs')) {
            // Fresh install: rebuild mail_logs directly from the canonical definition so a
            // fresh install is byte-for-byte identical to a migrated instance (the fleet has
            // drifted — utf8mb3/utf8mb4, datetime/timestamp, differing indexes). The table is
            // empty, so drop + recreate is instant. Drop the incoming FKs first (a referenced
            // table cannot be dropped); the outgoing FKs disappear together with the table.
            $this->dropForeignKeysReferencing('mail_logs');
            $this->execute("DROP TABLE mail_logs");
            $this->createCanonicalTable('mail_logs', $partitionsSql);
        } else {
            // Non-empty table: create the canonical shadow table. Data is moved by
            // MigrateMailLogsToPartitionsCommand; the FK on mail_log_conversions is
            // dropped there, right before the RENAME, when no live FK can block it.
            $this->createCanonicalTable(self::SHADOW_TABLE, $partitionsSql);
        }
    }

    public function down(): void
    {
        // Drop the shadow table if still present (non-empty path).
        if ($this->hasTable(self::SHADOW_TABLE)) {
            $this->table(self::SHADOW_TABLE)->drop()->save();
        }

        // The fresh-install rebuild (empty path) and the swapped-in partitioned table
        // cannot be automatically reversed.
    }

    // -------------------------------------------------------------------------

    /**
     * Refuses to run unless the older bigint migration (CreateNewMailLogsAndMailConversionsTable
     * plus its mail:migrate-mail-logs-and-conversions command) has been completed.
     *
     * That migration is what widens mail_logs.id to bigint and adds mail_logs.user_id, and
     * MailLogsPartitioningHelpersTrait copies user_id — so partitioning cannot work without it.
     * Its command was removed in 5.2.0, which means an unfinished bigint migration can only be
     * completed on an earlier release. Fail here, before anything is created, instead of
     * failing obscurely halfway through the go-live command.
     */
    private function assertBigintMigrationCompleted(): void
    {
        $leftovers = array_values(array_filter(
            ['mail_logs_v2', 'mail_log_conversions_v2'],
            fn (string $table) => $this->hasTable($table)
        ));

        if ($leftovers !== []) {
            throw new RuntimeException(sprintf(
                'Cannot partition mail_logs: leftover bigint migration table(s) %s found, which means '
                . '`mail:migrate-mail-logs-and-conversions` never finished. That command was removed in '
                . '5.2.0 — finish the bigint migration on a pre-5.2.0 release (and clean up with '
                . '`mail:bigint_migration_cleanup mail_logs` / `mail_log_conversions`) before upgrading.',
                implode(', ', $leftovers)
            ));
        }

        if (!$this->table('mail_logs')->hasColumn('user_id')) {
            throw new RuntimeException(
                'Cannot partition mail_logs: the `user_id` column is missing, which means the bigint '
                . 'migration (CreateNewMailLogsAndMailConversionsTable + '
                . '`mail:migrate-mail-logs-and-conversions`) never ran. That command was removed in '
                . '5.2.0 — complete the bigint migration on a pre-5.2.0 release before upgrading.'
            );
        }

        if ($this->hasTable('mail_logs_old')) {
            throw new RuntimeException(
                'Cannot partition mail_logs: `mail_logs_old` already exists. The go-live command renames '
                . 'mail_logs to mail_logs_old, so this leftover (from the bigint migration) must be '
                . 'archived and dropped first — `mail:bigint_migration_cleanup mail_logs`.'
            );
        }
    }

    /**
     * Creates the single canonical, partitioned mail_logs schema under $tableName.
     *
     * This is the one source of truth the whole fleet converges to: utf8mb4 /
     * utf8mb4_unicode_ci throughout (matching every other table converted in 2021 and
     * mail_job_queue.email used in JobQueueRepository joins), created_at/updated_at as
     * DATETIME (partitionable — RANGE COLUMNS cannot use TIMESTAMP), ROW_FORMAT=DYNAMIC
     * (so the 1020-byte utf8mb4 email(255) index stays under the 3072-byte prefix limit),
     * no hard_bounced_at, no foreign keys (InnoDB forbids them on partitioned tables), and
     * a composite PRIMARY KEY (id, created_at) as required for RANGE COLUMNS(created_at).
     */
    private function createCanonicalTable(string $tableName, string $partitionsSql): void
    {
        $this->execute("
            CREATE TABLE `{$tableName}` (
              `id`                   bigint       NOT NULL AUTO_INCREMENT,
              `email`                varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
              `user_id`              int          DEFAULT NULL,
              `created_at`           datetime     NOT NULL,
              `updated_at`           datetime     NOT NULL,
              `subject`              varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `mail_template_id`     int          DEFAULT NULL,
              `mail_job_id`          int          DEFAULT NULL,
              `mail_job_batch_id`    int          DEFAULT NULL,
              `mail_sender_id`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `context`              varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `delivered_at`         datetime     DEFAULT NULL,
              `dropped_at`           datetime     DEFAULT NULL,
              `spam_complained_at`   datetime     DEFAULT NULL,
              `clicked_at`           datetime     DEFAULT NULL,
              `opened_at`            datetime     DEFAULT NULL,
              `attachment_size`      int          DEFAULT NULL,
              PRIMARY KEY (`id`, `created_at`),
              KEY `email`                        (`email`),
              KEY `user_id`                      (`user_id`),
              KEY `mail_sender_id`               (`mail_sender_id`),
              KEY `delivered_at`                 (`delivered_at`),
              KEY `dropped_at`                   (`dropped_at`),
              KEY `spam_complained_at`           (`spam_complained_at`),
              KEY `clicked_at`                   (`clicked_at`),
              KEY `opened_at`                    (`opened_at`),
              KEY `updated_at`                   (`updated_at`),
              KEY `created_at`                   (`created_at`),
              KEY `mail_job_id`                  (`mail_job_id`, `email`),
              KEY `contexts`                     (`context`),
              KEY `mail_logs_mail_template_id`   (`mail_template_id`),
              KEY `mail_logs_mail_job_batch_id`  (`mail_job_batch_id`)
              -- No foreign keys: InnoDB forbids FKs on partitioned tables.
            ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            PARTITION BY RANGE COLUMNS (`created_at`) (
            {$partitionsSql}
            )
        ");
    }

    private function tableIsEmpty(string $table): bool
    {
        return $this->query("SELECT 1 FROM `{$table}` LIMIT 1")->fetch() === false;
    }

    /**
     * Builds the PARTITION BY RANGE COLUMNS clause body.
     *
     * Creates one partition per month from the earliest row in mail_logs through
     * six months into the future, plus a catch-all p_max partition.  The forward
     * horizon is intentionally short — SeedMailLogsPartitionsCommand is expected to
     * run monthly (or more often) to keep extending it — but the table still works
     * without that cron: once the pre-seeded months are exhausted, new rows simply
     * fall into p_max instead of a dedicated month.
     */
    private function buildPartitionDefinitions(): string
    {
        $row = $this->query('SELECT MIN(created_at) AS min_date FROM mail_logs')->fetch();
        $minDate = $row['min_date'] ?? null;

        if ($minDate !== null) {
            $start = new DateTime($minDate);
        } else {
            $start = new DateTime();
        }

        // Snap to the first day of the starting month.
        $start->modify('first day of this month')->setTime(0, 0, 0);

        // End boundary: 6 months from the current month.
        $end = (new DateTime('first day of this month 00:00:00'))->modify('+6 months');

        $parts = [];
        $current = clone $start;
        while ($current < $end) {
            $next = (clone $current)->modify('+1 month');
            $name = 'p_' . $current->format('Y_m');
            $parts[] = "    PARTITION `{$name}` VALUES LESS THAN ('{$next->format('Y-m-d')} 00:00:00')";
            $current = $next;
        }
        $parts[] = "    PARTITION `p_max` VALUES LESS THAN (MAXVALUE)";

        return implode(",\n", $parts);
    }

    /**
     * Drops every foreign key that REFERENCES $referencedTable (e.g. the
     * mail_log_conversions → mail_logs FK), so the referenced table can be dropped.
     * Constraint names differ across the fleet (*_v2 / *_v3 / *_ibfk_*), so they are
     * looked up dynamically rather than hardcoded.
     */
    private function dropForeignKeysReferencing(string $referencedTable): void
    {
        $rows = $this->query("
            SELECT TABLE_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME = '{$referencedTable}'
            GROUP BY TABLE_NAME, CONSTRAINT_NAME
        ")->fetchAll();

        foreach ($rows as $row) {
            $this->execute("ALTER TABLE `{$row['TABLE_NAME']}` DROP FOREIGN KEY `{$row['CONSTRAINT_NAME']}`");
        }
    }
}
