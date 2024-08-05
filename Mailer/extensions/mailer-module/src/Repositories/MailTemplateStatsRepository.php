<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Utils\DateTime;

class MailTemplateStatsRepository extends Repository
{
    protected $tableName = 'mail_template_stats';

    public function __construct(
        Explorer $database,
        private ListsRepository $listsRepository,
        ?Storage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
    }

    public function byDateAndMailTemplateId(DateTime $date, int $id): ?ActiveRow
    {
        return $this->getTable()
            ->where('mail_template_id', $id)
            ->where('date', $date->format('Y-m-d'))
            ->fetch();
    }

    public function byMailTemplateId(int $id): Selection
    {
        return $this->getTable()
            ->where('mail_template_id', $id);
    }

    public function byMailTypeId(int $id): Selection
    {
        return $this->getTable()
            ->where('mail_template.mail_type_id', $id)
            ->group('mail_template.mail_type_id');
    }

    public function all(): Selection
    {
        return $this->getTable();
    }

    public function getMailTypeGraphData(int $mailTypeId, DateTime $from, DateTime $to, string $groupBy = 'day'): Selection
    {
        $dateFormat = match ($groupBy) {
            'week' => '%x-%v',
            'month' => '%Y-%m',
            'day' => '%Y-%m-%d',
            default => throw new \Exception('Unrecognized $groupBy value:' . $groupBy),
        };

        return $this->getTable()
            ->select('
                SUM(COALESCE(mail_template_stats.sent, 0)) AS sent_mails,
                SUM(COALESCE(mail_template_stats.opened, 0)) AS opened_mails,
                SUM(COALESCE(mail_template_stats.clicked, 0)) AS clicked_mails,
                DATE_FORMAT(date, ?) AS label_date
            ', $dateFormat)
            ->where('mail_template.mail_type_id = ?', $mailTypeId)
            ->where('date >= DATE(?)', $from)
            ->where('date <= DATE(?)', $to)
            ->group('
                label_date
            ')
            ->order('label_date DESC');
    }

    public function getAllMailTemplatesGraphData(DateTime $from, DateTime $to): Selection
    {
        return $this->getTable()
            ->select('
                SUM(COALESCE(mail_template_stats.sent, 0)) AS sent_mails,
                date
            ')
            ->where('date > DATE(?)', $from)
            ->where('date <= DATE(?)', $to)
            ->group('date');
    }

    public function getTemplatesGraphDataGroupedByMailType(DateTime $from, DateTime $to): Selection
    {
        return $this->getTable()
            ->select('
                SUM(COALESCE(mail_template_stats.sent, 0)) AS sent_mails,
                mail_template.mail_type_id,
                date
            ')
            ->where('mail_template.mail_type_id', $this->listsRepository->all())
            ->where('mail_template_stats.date >= DATE(?)', $from)
            ->where('mail_template_stats.date <= DATE(?)', $to)
            ->group('
                date,
                mail_template.mail_type_id
            ')
            ->order('mail_template.mail_type_id')
            ->order('mail_template_stats.date DESC');
    }
}
