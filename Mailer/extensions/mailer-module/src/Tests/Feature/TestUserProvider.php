<?php

namespace Tests\Feature;

use Remp\MailerModule\Models\Users\IUser;

class TestUserProvider implements IUser
{
    private const PAGE_SIZE = 1000; // TODO: this is fixed in CRM, refactor

    private $users;

    /**
     * TestUserProvider constructor.
     *
     * @param array $users array of arrays having items 'id' and 'email'
     */
    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * List provides list of information about users.
     *
     * @param array $userIds List of userIDs to check. Empty array means all users.
     * @param int $page Page to obtain. Numbering starts with 1.
     * @return array
     */
    public function list(array $userIds, int $page, bool $includeDeactivated = false): array
    {
        $toReturn = [];
        foreach ($userIds as $userId) {
            if (isset($this->users[$userId])) {
                $toReturn[$userId] = $this->users[$userId];
            }
        }

        $offset = ($page-1) * self::PAGE_SIZE;
        return array_slice($toReturn, $offset, self::PAGE_SIZE, true);
    }
}
