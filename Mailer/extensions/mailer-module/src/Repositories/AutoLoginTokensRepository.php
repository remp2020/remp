<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Utils\DateTime;

class AutoLoginTokensRepository extends Repository
{
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

        return $this->getTable()->where([
            'email' => $emails
        ])->delete();
    }
}
