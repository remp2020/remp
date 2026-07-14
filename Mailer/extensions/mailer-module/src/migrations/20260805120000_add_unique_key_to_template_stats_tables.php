<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds a unique key on (mail_template_id, date) to both daily stats rollups.
 *
 * These tables are the only source template/newsletter statistics are read from, and
 * MailTemplateDirectStatsRepository::sumForTemplates() reads them as an unbounded lifetime
 * SUM - so a duplicate (mail_template_id, date) row inflates reported numbers permanently.
 * Duplicates were possible because mail:aggregate-mail-template-stats is expected to run
 * both per-minute (yesterday+today) and daily (trailing --from window), and the upsert used
 * to be a read-then-write with nothing preventing two runs from both inserting the same day.
 *
 * The unique key lets the repositories use INSERT ... ON DUPLICATE KEY UPDATE instead.
 */
final class AddUniqueKeyToTemplateStatsTables extends AbstractMigration
{
    private const TABLES = ['mail_template_stats', 'mail_template_direct_stats'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            $this->removeDuplicates($table);

            $this->table($table)
                ->addIndex(['mail_template_id', 'date'], [
                    'unique' => true,
                    'name' => $table . '_template_date',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            $this->restoreForeignKeyIndex($table);

            $this->table($table)
                ->removeIndexByName($table . '_template_date')
                ->update();
        }
    }

    /**
     * Both tables have a foreign key on mail_template_id, and InnoDB drops the index it
     * auto-created for it once the unique key added by up() can serve it (that index has
     * mail_template_id as its leftmost prefix). The unique key is then the only index backing
     * the constraint, and dropping it fails with "needed in a foreign key constraint" - so put
     * a dedicated index back first.
     */
    private function restoreForeignKeyIndex(string $table): void
    {
        if ($this->table($table)->hasIndexByName('mail_template_id')) {
            return;
        }

        $this->table($table)
            ->addIndex(['mail_template_id'], ['name' => 'mail_template_id'])
            ->update();
    }

    /**
     * Keeps the highest id per (mail_template_id, date) - the most recent writer computed the
     * day from the most complete mail_logs data - and deletes the rest, so the unique index
     * below can be created.
     */
    private function removeDuplicates(string $table): void
    {
        $this->execute("
            DELETE `dupe` FROM `{$table}` AS `dupe`
            JOIN (
                SELECT `mail_template_id`, `date`, MAX(`id`) AS `keep_id`
                FROM `{$table}`
                GROUP BY `mail_template_id`, `date`
                HAVING COUNT(*) > 1
            ) AS `keep`
              ON `dupe`.`mail_template_id` = `keep`.`mail_template_id`
             AND `dupe`.`date` = `keep`.`date`
             AND `dupe`.`id` <> `keep`.`keep_id`
        ");
    }
}
