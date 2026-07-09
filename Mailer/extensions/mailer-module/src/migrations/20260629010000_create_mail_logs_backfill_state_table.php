<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tracks per-month progress of BackfillMailLogsPartitionsCommand, which fills historical
 * (pre-go-live) mail_logs partitions via EXCHANGE PARTITION after
 * MigrateMailLogsToPartitionsCommand has put system/priority mail live.
 *
 * Rows are seeded by MigrateMailLogsToPartitionsCommand (one 'pending' row per historical
 * month partition still missing newsletter data) and consumed/updated by
 * BackfillMailLogsPartitionsCommand.
 *
 * The reserved `__swap__` row (status = 'swap', exchanged_at = go-live swap time) records
 * when mail_logs went live, so the backfill command knows the cutoff for reconciling any
 * live updates to system rows that landed in a historical partition after the swap.
 */
final class CreateMailLogsBackfillStateTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('mail_logs_backfill_state', ['id' => false, 'primary_key' => ['partition_name']])
            ->addColumn('partition_name', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'null' => false])
            ->addColumn('exchanged_at', 'datetime', ['null' => true])
            ->addTimestamps()
            ->create();
    }

    public function down(): void
    {
        $this->table('mail_logs_backfill_state')->drop()->save();
    }
}
