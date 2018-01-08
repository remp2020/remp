<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;

class LogEventsRepository extends Repository
{

    protected $tableName = 'mail_logs_events';

    public function addLog(IRow $log, DateTime $eventTime, $type)
    {
        return $this->insert([
            'mail_log_id' => $log->id,
            'created_at' => new DateTime(),
            'event_at' => $eventTime,
            'type' => $type,
        ]);
    }

    public function findByLogType($logId, $type)
    {
        return $this->getTable()->where([
            'mail_log_id' => $logId,
            'type' => $type,
        ])->fetch();
    }

    public function latestEventTime(): \DateTime
    {
        return $this->getTable()->max('event_at');
    }
}
