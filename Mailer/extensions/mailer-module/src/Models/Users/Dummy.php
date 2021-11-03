<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Users;

class Dummy implements IUser
{
    public function list(array $userIds, int $page, bool $includeDeactivated = false): array
    {
        if ($page > 1) {
            return [];
        }

        $users = [
            1 => ['id' => 1, 'email' => 'foo@example.com'],
            2 => ['id' => 2, 'email' => 'bar@example.com'],
        ];

        if ($includeDeactivated) {
            $users[3] = ['id' => 3, 'email' => 'deactivated@example.com'];
        }

        return $users;
    }
}
