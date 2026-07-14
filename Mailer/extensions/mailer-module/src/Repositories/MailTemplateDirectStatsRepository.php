<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

/**
 * Daily rollup of direct-send (mail_job_id IS NULL) mail_logs stats per mail_template_id
 * — see CreateMailTemplateDirectStatsTable. Schema-identical to MailTemplateStatsRepository's
 * table on purpose; populated by the same AggregateMailTemplateStatsCommand run.
 */
class MailTemplateDirectStatsRepository extends Repository
{
    use DailyTemplateStatsUpsertTrait;

    protected $tableName = 'mail_template_direct_stats';

    /**
     * Whether any rollup row exists strictly before $date — used to detect an empty/unbackfilled
     * table before mail_logs:migrate-to-partitions proceeds (see MigrateMailLogsToPartitionsCommand).
     */
    public function hasRowsBefore(\DateTimeInterface $date): bool
    {
        return $this->getTable()
            ->where('date < ?', $date->format('Y-m-d'))
            ->fetch() !== null;
    }

    /**
     * @param array<int, int> $templateIds
     * @return array{sent: int, delivered: int, opened: int, clicked: int, dropped: int, spam_complained: int, converted: int}
     */
    public function sumForTemplates(array $templateIds): array
    {
        $stats = [
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'dropped' => 0,
            'spam_complained' => 0,
            'converted' => 0,
        ];

        if (count($templateIds) === 0) {
            return $stats;
        }

        $row = $this->getTable()
            ->select('
                SUM(sent) AS sent,
                SUM(delivered) AS delivered,
                SUM(opened) AS opened,
                SUM(clicked) AS clicked,
                SUM(dropped) AS dropped,
                SUM(spam_complained) AS spam_complained,
                SUM(converted) AS converted
            ')
            ->where('mail_template_id', $templateIds)
            ->fetch();

        foreach ($stats as $key => $default) {
            $stats[$key] = (int) ($row->{$key} ?? $default);
        }

        return $stats;
    }
}
