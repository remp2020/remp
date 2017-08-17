<?php

namespace Remp\MailerModule\User;

interface IUser
{
    public function list(array $userIds, $page);
}
