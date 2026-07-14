<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use DateTime;

/**
 * Shared upsert for the two schema-identical daily stats rollups, `mail_template_stats`
 * and `mail_template_direct_stats`.
 *
 * A single INSERT ... ON DUPLICATE KEY UPDATE against the unique key on
 * (mail_template_id, date) - see AddUniqueKeyToTemplateStatsTables. This must not be a
 * read-then-write: mail:aggregate-mail-template-stats is expected to run per-minute
 * (yesterday+today) alongside a daily trailing --from window, so two runs regularly compute
 * the same day at the same time. A duplicate row would inflate the unbounded lifetime SUM
 * these tables are read with, permanently.
 */
trait DailyTemplateStatsUpsertTrait
{
    public function upsert(
        DateTime $date,
        int $mailTemplateId,
        int $sent,
        int $delivered,
        int $opened,
        int $clicked,
        int $dropped,
        int $spamComplained,
        int $converted,
    ): void {
        $this->database->query(
            "INSERT INTO `{$this->tableName}`
                (`mail_template_id`, `date`, `sent`, `delivered`, `opened`, `clicked`, `dropped`, `spam_complained`, `converted`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                `sent` = VALUES(`sent`),
                `delivered` = VALUES(`delivered`),
                `opened` = VALUES(`opened`),
                `clicked` = VALUES(`clicked`),
                `dropped` = VALUES(`dropped`),
                `spam_complained` = VALUES(`spam_complained`),
                `converted` = VALUES(`converted`)",
            $mailTemplateId,
            $date->format('Y-m-d'),
            $sent,
            $delivered,
            $opened,
            $clicked,
            $dropped,
            $spamComplained,
            $converted,
        );
    }
}
