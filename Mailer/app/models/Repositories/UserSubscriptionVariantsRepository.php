<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;
use Remp\MailerModule\ActiveRow;
use Nette\Utils\DateTime;

class UserSubscriptionVariantsRepository extends Repository
{
    protected $tableName = 'mail_user_subscription_variants';

    public function subscribedVariants(ActiveRow $userSubscription)
    {
        return $this->getTable()->where(['mail_user_subscription_id' => $userSubscription->id]);
    }

    public function multiSubscribedVariants($userSubscriptions)
    {
        return $this->getTable()
            ->where(['mail_user_subscription_id' => $userSubscriptions])
            ->select('mail_user_subscription_variants.*, mail_type_variant.code, mail_type_variant.title');
    }

    public function variantSubscribed(ActiveRow $userSubscription, $variantId)
    {
        return $this->getTable()->where([
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId,
        ])->count('*') > 0;
    }

    public function removeSubscribedVariants(ActiveRow $userSubscription)
    {
        return $this->getTable()
            ->where(['mail_user_subscription_id' => $userSubscription->id])
            ->delete();
    }

    public function removeSubscribedVariant(ActiveRow $userSubscription, int $variantId)
    {
        return $this->getTable()->where([
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId
        ])->delete();
    }

    public function addVariantSubscription(ActiveRow $userSubscription, int $variantId)
    {
        return $this->getTable()->insert([
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId,
            'created_at' => new DateTime(),
        ]);
    }

    public function variantsStats(ActiveRow $mailType, DateTime $from, DateTime $to)
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
