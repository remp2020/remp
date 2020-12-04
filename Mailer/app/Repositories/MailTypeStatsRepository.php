<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories;

class MailTypeStatsRepository extends Repository
{
    protected $tableName = 'mail_type_stats';

    public function add(
        int $mailTypeId,
        int $subscribersCount
    ) {
        return $this->getTable()->insert([
            'mail_type_id' => $mailTypeId,
            'created_at' => new DateTime(),
            'subscribers_count' => $subscribersCount,
        ]);
    }

    public function getDashboardDataGroupedByTypes(DateTime $from, DateTime $to)
    {
        return $this->getTable()
            ->select('mail_type_id, DATE(created_at) AS created_date, subscribers_count AS count')
            ->where('id IN (
                SELECT MAX(id) FROM mail_type_stats
                GROUP BY DATE(created_at), mail_type_id
            )')
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to)
            ->order('created_date ASC')
            ->fetchAll();
    }

    public function getDashboardDetailData($id, DateTime $from, DateTime $to)
    {
        return $this->getTable()
            ->select('SUM(subscribers_count) AS count, DATE(created_at) AS created_date')
            ->where('id IN (
                SELECT MAX(id) FROM mail_type_stats
                WHERE mail_type_id = ?
                GROUP BY DATE(created_at), mail_type_id
            )', $id)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to)
            ->group('created_date')
            ->order('created_date ASC')
            ->fetchAll();
    }
}
