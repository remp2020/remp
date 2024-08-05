<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Utils\DateTime;
use Remp\MailerModule\Hermes\HermesMessage;
use Tomaj\Hermes\Emitter;

class UserSubscriptionsRepository extends Repository
{
    use NewTableDataMigrationTrait;

    protected $tableName = 'mail_user_subscriptions';

    protected $dataTableSearchable = ['user_email'];

    public function __construct(
        Explorer $database,
        private UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository,
        private ListVariantsRepository $listVariantsRepository,
        private Emitter $emitter,
        Storage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
    }

    public function update(\Nette\Database\Table\ActiveRow $row, array $data): bool
    {
        $data['updated_at'] = new DateTime();
        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->where('id', $row->id)->update($data);
        }
        return parent::update($row, $data);
    }

    /**
     * @param array<string> $emails
     */
    public function deleteAllForEmails(array $emails): int
    {
        if (count($emails) === 0) {
            return 0;
        }

        $this->userSubscriptionVariantsRepository->removeSubscribedVariantsForEmails($emails);

        if ($this->newTableDataMigrationIsRunning()) {
            $this->getNewTable()->where([
                'user_email' => $emails
            ])->delete();
        }
        return $this->getTable()->where([
            'user_email' => $emails
        ])->delete();
    }

    public function findByUserId(int $userId): array
    {
        return $this->getTable()->where(['user_id' => $userId])->fetchAll();
    }

    public function findByEmail(string $email): array
    {
        return $this->getTable()->where(['user_email' => $email])->fetchAll();
    }

    public function allSubscribers(): array
    {
        $result = [];

        $lastUserId = 0;
        $query = $this->getTable()
            ->select('DISTINCT user_id')
            ->order('user_id')
            ->limit(10000);

        while (true) {
            $userIds = (clone $query)
                ->where('user_id > ?', $lastUserId)
                ->fetchPairs('user_id', 'user_id');

            if (!count($userIds)) {
                break;
            }

            $result += $userIds;
            $lastUserId = array_key_last($userIds);
        }

        return $result;
    }

    public function findSubscribedUserIdsByMailTypeCode(string $mailTypeCode): array
    {
        $result = [];

        $lastUserId = 0;
        $query = $this->getTable()
            ->select('DISTINCT user_id')
            ->where([
                'mail_type.code' => $mailTypeCode,
                'subscribed' => true,
            ])
            ->order('user_id')
            ->limit(10000);

        while (true) {
            $userIds = (clone $query)
                ->where('user_id > ?', $lastUserId)
                ->fetchPairs('user_id', 'user_id');

            if (!count($userIds)) {
                break;
            }

            $result += $userIds;
            $lastUserId = array_key_last($userIds);
        }

        return $result;
    }

    public function isEmailSubscribed(string $email, int $typeId): bool
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $typeId, 'subscribed' => true])->count('*') > 0;
    }

    public function isEmailUnsubscribed(string $email, int $typeId): bool
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $typeId, 'subscribed' => false])->count('*') > 0;
    }

    public function isUserUnsubscribed($userId, $mailTypeId): bool
    {
        return $this->getTable()->where(['user_id' => $userId, 'mail_type_id' => $mailTypeId, 'subscribed' => false])->count('*') > 0;
    }

    public function isUserSubscribed($userId, $mailTypeId): bool
    {
        return $this->getTable()->where(['user_id' => $userId, 'mail_type_id' => $mailTypeId, 'subscribed' => true])->count('*') > 0;
    }

    public function filterSubscribedEmails(array $emails, int $typeId): array
    {
        return $this->getTable()->where([
            'user_email' => $emails,
            'mail_type_id' => $typeId,
            'subscribed' => true,
        ])->select('user_email')->fetchPairs('user_email', 'user_email');
    }

    public function filterSubscribedEmailsAndIds(array $emails, int $typeId): array
    {
        return $this->getTable()->where([
            'user_email' => $emails,
            'mail_type_id' => $typeId,
            'subscribed' => true,
        ])->select('user_id, user_email')->fetchPairs('user_email', 'user_id');
    }

    public function subscribeUser(
        ActiveRow $mailType,
        int $userId,
        string $email,
        int $variantId = null,
        bool $sendWelcomeEmail = true,
        array $rtmParams = [],
        bool $forceNoVariantSubscription = false,
    ): ActiveRow {
        if ($variantId === null) {
            $variantId = $mailType->default_variant_id;
        }

        // TODO: handle user ID even when searching for actual subscription
        /** @var ActiveRow $actual */
        $actual = $this->getTable()
            ->where(['user_email' => $email, 'mail_type_id' => $mailType->id])
            ->limit(1)
            ->fetch();

        if (!$actual) {
            $actual = $this->insert([
                'user_id' => $userId,
                'user_email' => $email,
                'mail_type_id' => $mailType->id,
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
                'subscribed' => true,
                'rtm_source' => $rtmParams['rtm_source'] ?? null,
                'rtm_medium' => $rtmParams['rtm_medium'] ?? null,
                'rtm_campaign' => $rtmParams['rtm_campaign'] ?? null,
                'rtm_content' => $rtmParams['rtm_content'] ?? null,
            ]);
            $this->emitUserSubscribedEvent($userId, $email, $mailType->id, $sendWelcomeEmail, $rtmParams);
        } elseif (!$actual->subscribed) {
            $this->update($actual, [
                'subscribed' => true,
                'rtm_source' => $rtmParams['rtm_source'] ?? null,
                'rtm_medium' => $rtmParams['rtm_medium'] ?? null,
                'rtm_campaign' => $rtmParams['rtm_campaign'] ?? null,
                'rtm_content' => $rtmParams['rtm_content'] ?? null,
            ]);
            $actual = $this->find($actual->id);
            $this->emitUserSubscribedEvent($userId, $email, $mailType->id, $sendWelcomeEmail, $rtmParams);
        }

        if (!$forceNoVariantSubscription) {
            if ($variantId) {
                $variantSubscribed = $this->userSubscriptionVariantsRepository->variantSubscribed($actual, $variantId);
                if (!$variantSubscribed) {
                    if (!$mailType->is_multi_variant) {
                        $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
                    }
                    $this->userSubscriptionVariantsRepository->addVariantSubscription($actual, $variantId, $rtmParams);
                }
            } elseif (!$variantId && $mailType->is_multi_variant) {
                // subscribe all mail variants for multi_variant type without default variant
                foreach ($this->listVariantsRepository->getVariantsForType($mailType)->fetchAll() as $variant) {
                    $variantSubscribed = $this->userSubscriptionVariantsRepository->variantSubscribed($actual, $variant->id);
                    if (!$variantSubscribed) {
                        $this->userSubscriptionVariantsRepository->addVariantSubscription($actual, $variant->id, $rtmParams);
                    }
                }
            }
        }

        return $actual;
    }

    public function unsubscribeUser(
        ActiveRow $mailType,
        int $userId,
        string $email,
        array $rtmParams = [],
        bool $sendGoodbyeEmail = true
    ): void {
        // TODO: check for userId also when searching for actual subscription

        /** @var ActiveRow $actual */
        $actual = $this->getTable()->where([
            'user_email' => $email,
            'mail_type_id' => $mailType->id,
        ])->limit(1)->fetch();

        if (!$actual) {
            $this->insert([
                    'user_id' => $userId,
                    'user_email' => $email,
                    'mail_type_id' => $mailType->id,
                    'created_at' => new DateTime(),
                    'updated_at' => new DateTime(),
                    'subscribed' => false,
                    'rtm_source' => $rtmParams['rtm_source'] ?? null,
                    'rtm_medium' => $rtmParams['rtm_medium'] ?? null,
                    'rtm_campaign' => $rtmParams['rtm_campaign'] ?? null,
                    'rtm_content' => $rtmParams['rtm_content'] ?? null,
                ]);
        } else {
            if ($actual->subscribed) {
                $this->update($actual, [
                    'subscribed' => false,
                    'rtm_source' => $rtmParams['rtm_source'] ?? null,
                    'rtm_medium' => $rtmParams['rtm_medium'] ?? null,
                    'rtm_campaign' => $rtmParams['rtm_campaign'] ?? null,
                    'rtm_content' => $rtmParams['rtm_content'] ?? null,
                ]);
                $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
                $this->emitUserUnsubscribedEvent($userId, $email, $mailType->id, $sendGoodbyeEmail, $rtmParams);
            }
        }
    }

    public function unsubscribeUserFromAll(
        int $userId,
        string $email,
        array $filterOutMailTypeCodes = []
    ) {
        $subscriptions = $this->getTable()
            ->where('user_email', $email)
            ->where('user_id', $userId)
            ->where('mail_type.code NOT IN', $filterOutMailTypeCodes)
            ->where('subscribed', 1);

        foreach ($subscriptions as $subscription) {
            $this->update($subscription, [
                'subscribed' => false,
            ]);
            $this->userSubscriptionVariantsRepository->removeSubscribedVariants($subscription);
        }
    }

    public function unsubscribeEmail(ActiveRow $mailType, string $email, array $rtmParams = []): void
    {
        /** @var ActiveRow $actual */
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
                'rtm_source' => $rtmParams['rtm_source'] ?? null,
                'rtm_medium' => $rtmParams['rtm_medium'] ?? null,
                'rtm_campaign' => $rtmParams['rtm_campaign'] ?? null,
                'rtm_content' => $rtmParams['rtm_content'] ?? null,
            ]);

        $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
    }

    public function getAllSubscribersDataForMailTypes(array $mailTypeIds): Selection
    {
        return $this->getTable()
            ->select('COUNT(*) AS count, mail_type_id, subscribed')
            ->where('mail_type_id', $mailTypeIds)
            ->group('mail_type_id, subscribed');
    }

    public function getUserSubscription(ActiveRow $mailType, int $userId, string $email): ?ActiveRow
    {
        /** @var ActiveRow $row */
        $row = $this->getTable()->where([
            'user_id' => $userId,
            'mail_type_id' => $mailType->id,
            'user_email' => $email,
        ])->limit(1)->fetch();
        return $row;
    }

    public function getEmailSubscription(ActiveRow $mailType, string $email): ?ActiveRow
    {
        /** @var ActiveRow $row */
        $row = $this->getTable()->where([
            'mail_type_id' => $mailType->id,
            'user_email' => $email,
        ])->limit(1)->fetch();
        return $row;
    }

    public function unsubscribeUserVariant(
        ActiveRow $userSubscription,
        ActiveRow $variant,
        array $rtmParams = [],
        bool $sendGoodbyeEmail = true,
        bool $keepMailTypeSubscription = false
    ): void {
        $this->userSubscriptionVariantsRepository->removeSubscribedVariant($userSubscription, $variant->id);
        if (!$keepMailTypeSubscription &&
            $this->userSubscriptionVariantsRepository->subscribedVariants($userSubscription)->count('*') == 0) {
            $this->unSubscribeUser($userSubscription->mail_type, $userSubscription->user_id, $userSubscription->user_email, $rtmParams, $sendGoodbyeEmail);
        }
    }

    public function getMailTypeGraphData(int $mailTypeId, \DateTime $from, \DateTime $to, string $groupBy = 'day'): Selection
    {
        $dateFormat = match ($groupBy) {
            'week' => '%x-%v',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        return $this->getTable()
            ->select('
                count(id) AS unsubscribed_users, 
                DATE_FORMAT(updated_at, ?) AS label_date
            ', $dateFormat)
            ->where('subscribed = 0')
            ->where('mail_type_id = ?', $mailTypeId)
            ->where('updated_at >= DATE(?)', $from)
            ->where('updated_at <= DATE(?)', $to)
            ->where('updated_at != created_at')
            ->group('label_date')
            ->order('label_date DESC');
    }

    public function tableFilter(string $query, string $order, string $orderDirection, int $listId, ?int $limit = null, ?int $offset = null): Selection
    {
        $selection = $this->getTable()
            ->where([
                'mail_type_id' => $listId,
                'subscribed' => true
            ])
            ->order($order . ' ' . strtoupper($orderDirection));

        if (!empty($query)) {
            $where = [];
            foreach ($this->dataTableSearchable as $col) {
                $where[$col . ' LIKE ?'] = '%' . $query . '%';
            }

            $selection->whereOr($where);
        }

        if ($limit !== null) {
            $selection->limit($limit, $offset);
        }

        return $selection;
    }

    private function emitUserSubscribedEvent($userId, $email, $mailTypeId, $sendWelcomeEmail, $rtmParams = [])
    {
        $this->emitter->emit(new HermesMessage('user-subscribed', [
            'user_id' => $userId,
            'user_email' => $email,
            'mail_type_id' => $mailTypeId,
            'send_welcome_email' => $sendWelcomeEmail,
            'time' => new DateTime(),
            'rtm_source' => $rtmParams['rtm_source'] ?? null,
            'rtm_medium' => $rtmParams['rtm_medium'] ?? null,
            'rtm_campaign' => $rtmParams['rtm_campaign'] ?? null,
            'rtm_content' => $rtmParams['rtm_content'] ?? null,
        ]));
    }

    private function emitUserUnsubscribedEvent($userId, $email, $mailTypeId, $sendGoodbyeEmail, $rtmParams = [])
    {
        $this->emitter->emit(new HermesMessage('user-unsubscribed', [
            'user_id' => $userId,
            'user_email' => $email,
            'mail_type_id' => $mailTypeId,
            'send_goodbye_email' => $sendGoodbyeEmail,
            'time' => new DateTime(),
            'rtm_source' => $rtmParams['rtm_source'] ?? null,
            'rtm_medium' => $rtmParams['rtm_medium'] ?? null,
            'rtm_campaign' => $rtmParams['rtm_campaign'] ?? null,
            'rtm_content' => $rtmParams['rtm_content'] ?? null,
        ]));
    }
}
