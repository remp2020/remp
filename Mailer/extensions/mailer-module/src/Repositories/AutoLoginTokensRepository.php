<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;
use Remp\MailerModule\Models\DataRetentionInterface;
use Remp\MailerModule\Models\DataRetentionTrait;

class AutoLoginTokensRepository extends Repository implements DataRetentionInterface
{
    use NewTableDataMigrationTrait;
    use DataRetentionTrait;

    protected $tableName = 'autologin_tokens';

    public function getInsertData(string $token, string $email, DateTime $validFrom, DateTime $validTo, int $maxCount = 1): array
    {
        return [
            'token' => $token,
            'email' => $email,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'max_count' => $maxCount,
            'used_count' => 0,
            'created_at' => new DateTime(),
        ];
    }

    /**
     * @param array<string> $emails
     */
    public function deleteAllForEmails(array $emails): int
    {
        if (count($emails) === 0) {
            return 0;
        }

        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->where([
                'email' => $emails
            ])->delete();
        }

        return $this->getTable()->where([
            'email' => $emails
        ])->delete();
    }
}
