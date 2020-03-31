<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;
use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;

class UserSubscriptionVariantsRepository extends Repository
{
    protected $tableName = 'mail_user_subscription_variants';

    public function subscribedVariants(IRow $userSubscription)
    {
        return $this->getTable()->where(['mail_user_subscription_id' => $userSubscription->id]);
    }

    public function multiSubscribedVariants($userSubscriptions)
    {
        return $this->getTable()
            ->where(['mail_user_subscription_id' => $userSubscriptions])
            ->select('mail_user_subscription_variants.*, mail_type_variant.code, mail_type_variant.title');
    }

    public function variantSubscribed(IRow $userSubscription, $variantId)
    {
        return $this->getTable()->where([
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId,
        ])->count('*') > 0;
    }

    public function removeSubscribedVariants(IRow $userSubscription)
    {
        return $this->getTable()
            ->where(['mail_user_subscription_id' => $userSubscription->id])
            ->delete();
    }

    public function removeSubscribedVariant(IRow $userSubscription, $variantId)
    {
        return $this->getTable()->where([
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId
        ])->delete();
    }

    public function addVariantSubscription(IRow $userSubscription, $variantId)
    {
        return $this->getTable()->insert([
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId,
            'created_at' => new DateTime(),
        ]);
    }

    public function variantsStats(IRow $mailType, DateTime $from, DateTime $to)
    {
        return $this->getTable()->where([
            'mail_user_subscription.subscribed' => 1,
            'mail_user_subscription.mail_type_id' => $mailType->id,
            'mail_user_subscription_variants.created_at > ?' => $from,
            'mail_user_subscription_variants.created_at < ?' => $to,
        ])->group('mail_type_variant_id')
            ->select('COUNT(*) AS count, mail_type_variant_id, MAX(mail_type_variant.title) AS title, MAX(mail_type_variant.code) AS code')
            ->order('count DESC');
    }
}
