<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Database\Table\ActiveRow as NetteActiveRow;
use Nette\Utils\DateTime;

class LogConversionsRepository extends Repository
{
    use NewTableDataMigrationTrait;

    protected $tableName = 'mail_log_conversions';

    public function upsert(NetteActiveRow $mailLog, DateTime $convertedAt): void
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

    public function deleteForMailLogs(array $mailLogIds): int
    {
        if (empty($mailLogIds)) {
            return 0;
        }

        $result = $this->getTable()->where([
            'mail_log_id IN (?)' => $mailLogIds
        ])->delete();

        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->where([
                'mail_log_id IN (?)' => $mailLogIds
            ])->delete();
        }

        return $result;
    }
}
