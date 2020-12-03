<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repository;

use Nette\Utils\DateTime;
use Remp\MailerModule\ActiveRow;
use Remp\MailerModule\Repository;

class LogConversionsRepository extends Repository
{
    protected $tableName = 'mail_log_conversions';

    public function upsert(ActiveRow $mailLog, DateTime $convertedAt)
    {
        $conversion = $this->getTable()->where([
            'mail_log_id' => $mailLog->id,
        ])->fetch();

        if ($conversion) {
            $this->update($conversion, [
                'converted_at' => $convertedAt,
            ]);
        } else {
            $this->insert([
                'mail_log_id' => $mailLog->id,
                'converted_at' => $convertedAt,
            ]);
        }
    }
}
