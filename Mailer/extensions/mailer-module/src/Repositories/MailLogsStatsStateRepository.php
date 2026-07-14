<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;

/**
 * Single-row table holding the rolling cutoff date — see CreateMailLogsStatsStateTable.
 * `cutoff_date IS NULL` means no cutoff is active (sealing disabled, e.g. an install that
 * never prunes mail_logs); everything below is a no-op in that case except initCutoffDate()
 * and raiseCutoffDateTo(), which are the only two operations allowed to move the cutoff
 * date away from NULL.
 */
class MailLogsStatsStateRepository extends Repository
{
    protected $tableName = 'mail_logs_stats_state';

    private const ROW_ID = 1;

    public function getCutoffDate(): ?\DateTimeInterface
    {
        $row = $this->getTable()->where('id', self::ROW_ID)->fetch();
        return $row?->cutoff_date;
    }

    /**
     * Sets the cutoff date to $date only if it is currently unset (NULL). Idempotent —
     * safe to call on every run of MigrateMailLogsToPartitionsCommand.
     */
    public function initCutoffDate(\DateTimeInterface $date): void
    {
        $row = $this->getTable()->where('id', self::ROW_ID)->fetch();

        if ($row === null) {
            $this->getTable()->insert([
                'id' => self::ROW_ID,
                'cutoff_date' => $date,
                'updated_at' => new DateTime(),
            ]);
            return;
        }

        if ($row->cutoff_date === null) {
            $this->getTable()->where('id', self::ROW_ID)->update([
                'cutoff_date' => $date,
                'updated_at' => new DateTime(),
            ]);
        }
    }

    /**
     * Moves the cutoff date to min(current, $date) — only ever earlier. A NULL cutoff date
     * is left untouched: it means sealing was never activated (initCutoffDate not yet run),
     * and there is nothing to converge towards.
     */
    public function lowerCutoffDateTo(\DateTimeInterface $date): void
    {
        $row = $this->getTable()->where('id', self::ROW_ID)->fetch();
        if ($row === null || $row->cutoff_date === null) {
            return;
        }

        if ($date < $row->cutoff_date) {
            $this->getTable()->where('id', self::ROW_ID)->update([
                'cutoff_date' => $date,
                'updated_at' => new DateTime(),
            ]);
        }
    }

    /**
     * Moves the cutoff date to max(current, $date) — only ever later — and activates it
     * (from NULL) if it wasn't set yet, since pruning below $date means data before it is
     * now genuinely gone regardless of whether the cutoff date lifecycle had been
     * initialized.
     */
    public function raiseCutoffDateTo(\DateTimeInterface $date): void
    {
        $row = $this->getTable()->where('id', self::ROW_ID)->fetch();

        if ($row === null) {
            $this->getTable()->insert([
                'id' => self::ROW_ID,
                'cutoff_date' => $date,
                'updated_at' => new DateTime(),
            ]);
            return;
        }

        if ($row->cutoff_date === null || $date > $row->cutoff_date) {
            $this->getTable()->where('id', self::ROW_ID)->update([
                'cutoff_date' => $date,
                'updated_at' => new DateTime(),
            ]);
        }
    }
}
