<?php

namespace Remp\MailerModule\Auth;

use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repository\AutoLoginTokensRepository;

class AutoLogin
{
    /** @var AutoLoginTokensRepository */
    private $autoLoginTokensRepository;

    public function __construct(AutoLoginTokensRepository $autoLoginTokensRepository)
    {
        $this->autoLoginTokensRepository = $autoLoginTokensRepository;
    }

    public function getToken($token)
    {
        return $this->autoLoginTokensRepository->findBy('token', $token);
    }

    public function useToken(IRow $token)
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
            $token = md5($email . microtime(true) . rand(100, 100000) . rand(10000, 1000000));
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
