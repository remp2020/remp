<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;

class AutoLoginTokensRepository extends Repository
{
    protected $tableName = 'autologin_tokens';

    public function add($token, $email, $validFrom, $validTo, $maxCount = 1)
    {
        return $this->insert([
            'token' => $token,
            'email' => $email,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'max_count' => $maxCount,
            'used_count' => 0,
            'created_at' => new \DateTime(),
        ]);
    }
}
