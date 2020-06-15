<?php

namespace Remp\MailerModule\Repository;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repository;

class UserSubscriptionsRepository extends Repository
{
    protected $tableName = 'mail_user_subscriptions';

    private $userSubscriptionVariantsRepository;

    public function __construct(
        Context $database,
        UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository,
        IStorage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
        $this->userSubscriptionVariantsRepository = $userSubscriptionVariantsRepository;
    }

    public function update(IRow &$row, $data)
    {
        $params['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }

    public function findByUserId($userId)
    {
        return $this->getTable()->where(['user_id' => $userId])->fetchAll();
    }

    public function findByEmail($email)
    {
        return $this->getTable()->where(['user_email' => $email])->fetchAll();
    }

    public function findByEmailList($email, $listId)
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $listId])->fetch();
    }

    public function isEmailSubscribed($email, $typeId)
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $typeId, 'subscribed' => true])->count('*') > 0;
    }

    public function isEmailUnsubscribed($email, $typeId)
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $typeId, 'subscribed' => false])->count('*') > 0;
    }

    public function isUserUnsubscribed($userId, $mailTypeId)
    {
        return $this->getTable()->where(['user_id' => $userId, 'mail_type_id' => $mailTypeId, 'subscribed' => false])->count('*') > 0;
    }

    public function filterSubscribedEmails(array $emails, $typeId)
    {
        return $this->getTable()->where([
            'user_email' => $emails,
            'mail_type_id' => $typeId,
            'subscribed' => true,
        ])->select('user_email')->fetchPairs('user_email', 'user_email');
    }

    public function subscribeUser(ActiveRow $mailType, $userId, $email, $variantId = null)
    {
        if ($variantId == null) {
            $variantId = $mailType->default_variant_id;
        }

        // TODO: handle user ID even when searching for actual subscription
        $actual = $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $mailType->id])->limit(1)->fetch();
        if (!$actual) {
            $actual = $this->getTable()->insert([
                'user_id' => $userId,
                'user_email' => $email,
                'mail_type_id' => $mailType->id,
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
                'subscribed' => true,
            ]);

            if ($variantId) {
                if (!$mailType->is_multi_variant) {
                    $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
                }
                $this->userSubscriptionVariantsRepository->addVariantSubscription($actual, $variantId);
            }
        } else {
            if (!$actual->subscribed) {
                $this->update($actual, ['subscribed' => true]);
            }

            if ($variantId) {
                $variantExists = $this->userSubscriptionVariantsRepository->variantSubscribed($actual, $variantId);
                if (!$variantExists) {
                    if (!$mailType->is_multi_variant) {
                        $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
                    }
                    $this->userSubscriptionVariantsRepository->addVariantSubscription($actual, $variantId);
                }
            }
        }
    }

    public function unsubscribeUser(ActiveRow $mailType, $userId, $email, $utmParams = [])
    {
        // TODO: check for userId also when searching for actual subscription
        $actual = $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $mailType->id])->limit(1)->fetch();
        if (!$actual) {
            $this->getTable()->insert([
                    'user_id' => $userId,
                    'user_email' => $email,
                    'mail_type_id' => $mailType->id,
                    'created_at' => new DateTime(),
                    'updated_at' => new DateTime(),
                    'subscribed' => false,
                ] + $utmParams);
        } else {
            if ($actual->subscribed) {
                $this->getTable()->where([
                    'user_id' => $userId,
                    'mail_type_id' => $mailType->id,
                ])->update(['subscribed' => false, 'updated_at' => new DateTime()] + $utmParams);
                $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
            }
        }
    }

    public function unsubscribeEmail(ActiveRow $mailType, $email, $utmParams = [])
    {
        $actual = $this->getTable()->where([
            'user_email' => $email,
            'mail_type_id' => $mailType->id,
            'subscribed' => true,
        ])->limit(1)->fetch();

        if (!$actual) {
            return;
        }

        $this->update($actual, [
                'subscribed' => false,
                'updated_at' => new DateTime(),
            ] + $utmParams);
        
        $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
    }

    public function getAllSubscribersDataForMailTypes(array $mailTypeIds)
    {
        return $this->getTable()
            ->select('COUNT(*) AS count, mail_type_id, subscribed')
            ->where('mail_type_id', $mailTypeIds)
            ->group('mail_type_id, subscribed');
    }

    public function getUserSubscription(ActiveRow $mailType, $userId, $email)
    {
        return $this->getTable()->where(['user_id' => $userId, 'mail_type_id' => $mailType->id, 'user_email' => $email])->limit(1)->fetch();
    }

    public function unsubscribeUserVariant(ActiveRow $userSubscription, ActiveRow $variant, $utmParams = [])
    {
        $this->userSubscriptionVariantsRepository->removeSubscribedVariant($userSubscription, $variant->id);
        if ($this->userSubscriptionVariantsRepository->subscribedVariants($userSubscription)->count('*') == 0) {
            $this->unSubscribeUser($userSubscription->mail_type, $userSubscription->user_id, $userSubscription->user_email, $utmParams);
        }
    }
}
