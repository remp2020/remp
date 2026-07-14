<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Single-row table storing the rolling cutoff date — the date before which mail_logs
 * data may be incomplete (pruned) and persisted aggregates derived from it must not be
 * blindly recomputed. `cutoff_date IS NULL` (the default) means no cutoff is active, i.e.
 * sealing is disabled and behavior is unchanged from before mail_logs pruning existed.
 *
 * Lifecycle (see MailLogsStatsStateRepository):
 *   - MigrateMailLogsToPartitionsCommand seeds it (once, if still NULL) to the start of
 *     the live window at go-live (first day of the previous month).
 *   - BackfillMailLogsPartitionsCommand lowers it as each historical month is fully
 *     loaded, converging to the real retention cutoff even if backfill is interrupted
 *     or resumed.
 *   - PruneMailLogsPartitionsCommand raises it after every successful prune run to the
 *     cutoff date used, since newsletter data below it is now genuinely gone.
 */
final class CreateMailLogsStatsStateTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_logs_stats_state', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'signed' => false, 'null' => false, 'default' => 1])
            ->addColumn('cutoff_date', 'datetime', ['null' => true])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('mail_logs_stats_state')->drop()->save();
    }
}
