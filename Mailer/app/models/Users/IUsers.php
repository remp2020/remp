<?php

namespace Remp\MailerModule\User;

interface IUser
{
    /**
     * List provides list of information about users.
     *
     * @param array $userIds List of userIDs to check. Empty array means all users.
     * @param integer $page Page to obtain. Numbering starts with 1.
     * @return mixed
     */
    public function list(array $userIds, $page);
}
