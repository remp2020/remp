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
}
