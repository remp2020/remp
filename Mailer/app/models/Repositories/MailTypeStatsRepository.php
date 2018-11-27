<?php

namespace Remp\MailerModule\Repository;

use DateTime;
use Remp\MailerModule\Repository;

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
            ->select('mail_type_id, MAX(subscribers_count) AS count, DATE(created_at) AS created_date')
            ->where('DATE(created_at) >= DATE(?)', $from->format('Y-m-d'))
            ->where('DATE(created_at) <= DATE(?)', $to->format('Y-m-d'))
            ->group('created_date, mail_type_id')
            ->order('mail_type_id, created_date DESC')
            ->fetchAll();
    }

    public function getDashboardDetailData($id, DateTime $from, DateTime $to)
    {
        return $this->getTable()
            ->select('mail_type_id, MAX(subscribers_count) AS count, DATE(created_at) AS created_date')
            ->where('DATE(created_at) >= DATE(?)', $from->format('Y-m-d'))
            ->where('DATE(created_at) <= DATE(?)', $to->format('Y-m-d'))
            ->where('mail_type_id = ?', $id)
            ->group('created_date, mail_type_id')
            ->order('mail_type_id, created_date DESC')
            ->fetchAll();
    }

    public function getDashboardData(DateTime $from, DateTime $to)
    {
        return $this->getTable()
            ->select('MAX(subscribers_count) AS count, DATE(created_at) AS created_date')
            ->where('DATE(created_at) >= DATE(?)', $from)
            ->where('DATE(created_at) < DATE(?)', $to)
            ->group('created_date')
            ->order('created_date DESC')
            ->fetchAll();
    }
}
