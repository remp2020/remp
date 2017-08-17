<?php

namespace Remp\MailerModule\Repository;

use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;
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

    /**
     * @param ActiveRow $mailType
     * @param integer $userId
     * @return bool|int|IRow
     * @throws \Exception
     */
    public function autoSubscribe($mailType, $userId, $email)
    {
        $userSubscription = $this->getTable()
            ->where([
                'mail_type_id' => $mailType->id,
                'user_id' => $userId,
            ])
            ->count('*');
        if ($userSubscription) {
            return $userSubscription;
        }

        $variants = $mailType->related('mail_type_variants')->fetchAll();
        if ($mailType->auto_subscribe && count($variants) > 1) {
            throw new \Exception("unable to auto-subscribe user, don't know which variant to choose");
        }
        $data = [
            'subscribed' => $mailType->auto_subscribe,
            'user_id' => $userId,
            'mail_type_id' => $mailType->id,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
            'user_email' => $email, // TODO: do we need this here? it's required...
        ];
        if (count($variants) == 1) {
            $data['mail_type_variant_id'] = $variants[0]->id;
        }
        return $this->insert($data);
    }
}
