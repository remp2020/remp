<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Auth;

use Remp\MailerModule\Repositories\ActiveRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repositories\AutoLoginTokensRepository;

class AutoLogin
{
    /** @var AutoLoginTokensRepository */
    private $autoLoginTokensRepository;

    public function __construct(AutoLoginTokensRepository $autoLoginTokensRepository)
    {
        $this->autoLoginTokensRepository = $autoLoginTokensRepository;
    }

    public function getToken($token): ?ActiveRow
    {
        /** @var ActiveRow $tokenRow */
        $tokenRow = $this->autoLoginTokensRepository->findBy('token', $token);
        if (!$tokenRow) {
            return null;
        }
        return $tokenRow;
    }

    public function useToken(ActiveRow $token): bool
    {
        return $this->autoLoginTokensRepository->update($token, ['used_count+=' => 1]);
    }

    public function createTokens(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        $autologinInsertData = [];
        $returnData = [];

        $validFrom = new DateTime();
        $validTo = $validFrom->modifyClone('+1 month');

        foreach ($emails as $email) {
            $token = TokenGenerator::generate();
            $autologinInsertData[] = $this->autoLoginTokensRepository->getInsertData(
                $token,
                $email,
                $validFrom,
                $validTo,
                10
            );

            $returnData[$email] = $token;
        }

        $autologinTokensTableName = $this->autoLoginTokensRepository->getTable()->getName();
        $this->autoLoginTokensRepository->getDatabase()->query("INSERT INTO $autologinTokensTableName", $autologinInsertData);

        return $returnData;
    }
}
