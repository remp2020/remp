<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository;

class UserSubscriptionsRepository extends Repository
{
    protected $tableName = 'mail_user_subscriptions';

    public function update(IRow &$row, $data)
    {
        $params['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }

    public function findByEmailList($email, $listId)
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $listId])->fetch();
    }

    public function isEmailSubscribed($email, $typeId)
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $typeId, 'subscribed' => true])->count('*') > 0;
    }
}
