<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Hermes\HermesMessage;
use Tomaj\Hermes\Emitter;

class UserSubscriptionVariantsRepository extends Repository
{
    use NewTableDataMigrationTrait;

    protected $tableName = 'mail_user_subscription_variants';

    public function __construct(
        Explorer $database,
        private Emitter $emitter,
        ?Storage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
    }

    public function subscribedVariants(ActiveRow $userSubscription): Selection
    {
        return $this->getTable()->where(['mail_user_subscription_id' => $userSubscription->id]);
    }

    public function multiSubscribedVariants($userSubscriptions): Selection
    {
        return $this->getTable()
            ->where(['mail_user_subscription_id' => $userSubscriptions])
            ->select('mail_user_subscription_variants.*, mail_type_variant.code, mail_type_variant.title');
    }

    public function variantSubscribed(ActiveRow $userSubscription, int $variantId): bool
    {
        return $this->getTable()->where([
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId,
        ])->count('*') > 0;
    }

    public function removeSubscribedVariants(ActiveRow $userSubscription): int
    {
        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()
                ->where(['mail_user_subscription_id' => $userSubscription->id])
                ->delete();
        }
        return $this->getTable()
            ->where(['mail_user_subscription_id' => $userSubscription->id])
            ->delete();
    }

    /**
     * @param array<string> $emails
     */
    public function removeSubscribedVariantsForEmails(array $emails): int
    {
        /* use nicer delete when bug in nette/database is fixed https://github.com/nette/database/issues/255

        return $this->getTable()
            ->where('mail_user_subscription.user_email', $email)
            ->delete();
        */

        $variantsToRemove = $this->getTable()
            ->where(['mail_user_subscription.user_email' => $emails])
            ->fetchAll();

        $result = 0;
        foreach ($variantsToRemove as $variant) {
            $deleted = $this->delete($variant);
            if ($deleted) {
                $result++;
            }
        }

        return $result;
    }

    public function removeSubscribedVariant(ActiveRow $userSubscription, int $variantId): int
    {
        $where = [
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId
        ];

        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->where($where)->delete();
        }

        $this->emitter->emit(new HermesMessage('user-unsubscribed-variant', [
            'user_id' => $userSubscription->user_id,
            'user_email' => $userSubscription->user_email,
            'mail_type_id' => $userSubscription->mail_type->id,
            'mail_type_variant_id' => $variantId,
            'time' => new DateTime(),
        ]));
        return $this->getTable()->where($where)->delete();
    }

    public function addVariantSubscription(ActiveRow $userSubscription, int $variantId, array $rtmParams = []): ActiveRow
    {
        $this->emitter->emit(new HermesMessage('user-subscribed-variant', [
            'user_id' => $userSubscription->user_id,
            'user_email' => $userSubscription->user_email,
            'mail_type_id' => $userSubscription->mail_type->id,
            'mail_type_variant_id' => $variantId,
            'time' => new DateTime(),
            'rtm_source' => $rtmParams['rtm_source'] ?? null,
            'rtm_medium' => $rtmParams['rtm_medium'] ?? null,
            'rtm_campaign' => $rtmParams['rtm_campaign'] ?? null,
            'rtm_content' => $rtmParams['rtm_content'] ?? null,
        ]));

        return $this->insert([
            'mail_user_subscription_id' => $userSubscription->id,
            'mail_type_variant_id' => $variantId,
            'created_at' => new DateTime(),
        ]);
    }

    public function variantsStats(ActiveRow $mailType, DateTime $from, DateTime $to): Selection
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

    public function delete(ActiveRow &$row): bool
    {
        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->wherePrimary($row->getPrimary())->delete();
        }
        return parent::delete($row);
    }
}
