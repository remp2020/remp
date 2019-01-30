<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repository;

class LogConversionsRepository extends Repository
{
    protected $tableName = 'mail_log_conversions';

    public function upsert(ActiveRow $mailLog, \DateTime $convertedAt)
    {
        return $this->getTable()->where([
            'mail_log_id' => $mailLog->id,
        ])->update([
            'converted_at' => $convertedAt,
        ]);
    }
}
