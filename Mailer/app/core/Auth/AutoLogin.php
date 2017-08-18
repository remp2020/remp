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

    public function createToken($email)
    {
        $token = md5($email . microtime(true) . rand(100, 100000) . rand(10000, 1000000));

        $validFrom = new DateTime();
        $validTo = $validFrom->modifyClone('+1 month');

        return $this->autoLoginTokensRepository->add(
            $token,
            $email,
            $validFrom,
            $validTo,
            10
        );
    }
}
