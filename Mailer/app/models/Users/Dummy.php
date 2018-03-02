<?php

namespace Remp\MailerModule\User;

class Dummy implements IUser
{
    public function list(array $userIds, $page)
    {
        return array_intersect($userIds, [
            1 => ['id' => 1, 'email' => 'foo@example.com'],
            2 => ['id' => 2, 'email' => 'bar@example.com'],
        ]);
    }
}
