<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Users;

interface IUser
{
    /**
     * List provides list of information about users.
     *
     * @param array $userIds List of userIDs to check. Empty array means all users.
     * @param int $page Page to obtain. Numbering starts with 1.
     * @return array
     */
    public function list(array $userIds, int $page, bool $includeDeactivated = false): array;
}
