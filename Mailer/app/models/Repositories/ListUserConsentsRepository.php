<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;

class ListUserConsentsRepository extends Repository
{
    protected $tableName = 'list_user_consents';

    public function add($userId, $listId)
    {
        $result = $this->insert([
            'user_id' => $userId,
            'list_id' => $listId,
            'created_at' => new \DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }

    public function isUserSubscribed($userId, $list)
    {
        $consents = $this->getTable()->where(['user_id' => $userId, 'list_id' => $list->id])->count('*');
        return ($list->is_consent_required == 1 && $consents > 0) ||
            ($list->is_consent_required == 0 && $consents == 0);
    }
}
