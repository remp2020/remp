<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Utils\DateTime;

class MailTypeStatsRepository extends Repository
{
    protected $tableName = 'mail_type_stats';

    public function __construct(
        Explorer $database,
        private ListsRepository $listsRepository,
        ?Storage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
    }

    public function add(
        int $mailTypeId,
        int $subscribersCount
    ): ActiveRow {
        return $this->getTable()->insert([
            'mail_type_id' => $mailTypeId,
            'created_at' => new DateTime(),
            'subscribers_count' => $subscribersCount,
        ]);
    }

    public function getDashboardDataGroupedByTypes(DateTime $from, DateTime $to): array
    {
        return $this->getTable()
            ->select('mail_type_id, DATE(created_at) AS created_date, MAX(subscribers_count) AS count')
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to)
            ->where('mail_type_id', $this->listsRepository->all())
            ->group('mail_type_id, created_date')
            ->order('created_date ASC')
            ->fetchAll();
    }

    public function getDashboardDetailData($id, DateTime $from, DateTime $to): array
    {
        return $this->getTable()
            ->select('MAX(subscribers_count) AS count, DATE(created_at) AS created_date')
            ->where('mail_type_id = ?', $id)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to)
            ->group('mail_type_id, created_date')
            ->order('mail_type_id ASC, created_date ASC')
            ->fetchAll();
    }
}
