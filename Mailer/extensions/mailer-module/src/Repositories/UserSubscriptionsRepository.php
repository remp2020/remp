<?php
declare(strict_types=1);

namespace Remp\MailerModule\Repositories;

use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Utils\DateTime;
use Remp\MailerModule\Hermes\HermesMessage;
use Tomaj\Hermes\Emitter;

class UserSubscriptionsRepository extends Repository
{
    protected $tableName = 'mail_user_subscriptions';

    protected $dataTableSearchable = ['user_email'];

    private $userSubscriptionVariantsRepository;

    private $emitter;

    public function __construct(
        Context $database,
        UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository,
        Emitter $emitter,
        IStorage $cacheStorage = null
    ) {
        parent::__construct($database, $cacheStorage);
        $this->userSubscriptionVariantsRepository = $userSubscriptionVariantsRepository;
        $this->emitter = $emitter;
    }

    public function update(ActiveRow &$row, array $data): bool
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function findByUserId(int $userId): array
    {
        return $this->getTable()->where(['user_id' => $userId])->fetchAll();
    }

    public function findByEmail(string $email): array
    {
        return $this->getTable()->where(['user_email' => $email])->fetchAll();
    }

    public function findByEmailList(string $email, int $listId): ?ActiveRow
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $listId])->fetch();
    }

    public function isEmailSubscribed(string $email, int $typeId): bool
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $typeId, 'subscribed' => true])->count('*') > 0;
    }

    public function isEmailUnsubscribed(string $email, int $typeId): bool
    {
        return $this->getTable()->where(['user_email' => $email, 'mail_type_id' => $typeId, 'subscribed' => false])->count('*') > 0;
    }

    public function isUserUnsubscribed($userId, $mailTypeId)
    {
        return $this->getTable()->where(['user_id' => $userId, 'mail_type_id' => $mailTypeId, 'subscribed' => false])->count('*') > 0;
    }

    public function filterSubscribedEmails(array $emails, int $typeId): array
    {
        return $this->getTable()->where([
            'user_email' => $emails,
            'mail_type_id' => $typeId,
            'subscribed' => true,
        ])->select('user_email')->fetchPairs('user_email', 'user_email');
    }

    public function subscribeUser(
        ActiveRow $mailType,
        int $userId,
        string $email,
        int $variantId = null,
        bool $sendWelcomeEmail = true
    ): void {
        if ($variantId == null) {
            $variantId = $mailType->default_variant_id;
        }

        // TODO: handle user ID even when searching for actual subscription
        $actual = $this->getTable()
            ->where(['user_email' => $email, 'mail_type_id' => $mailType->id])
            ->limit(1)
            ->fetch();

        if (!$actual) {
            $actual = $this->getTable()->insert([
                'user_id' => $userId,
                'user_email' => $email,
                'mail_type_id' => $mailType->id,
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
                'subscribed' => true,
            ]);
            $this->emitUserSubscribedEvent($userId, $email, $mailType->id, $sendWelcomeEmail);

            if ($variantId) {
                if (!$mailType->is_multi_variant) {
                    $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
                }
                $this->userSubscriptionVariantsRepository->addVariantSubscription($actual, $variantId);
            }
        } else {
            if (!$actual->subscribed) {
                $this->update($actual, ['subscribed' => true]);
                $this->emitUserSubscribedEvent($userId, $email, $mailType->id, $sendWelcomeEmail);
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

    public function unsubscribeUser(ActiveRow $mailType, int $userId, string $email, array $rtmParams = []): void
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
                ] + $rtmParams);
        } else {
            if ($actual->subscribed) {
                $this->getTable()->where([
                    'user_id' => $userId,
                    'mail_type_id' => $mailType->id,
                ])->update(['subscribed' => false, 'updated_at' => new DateTime()] + $rtmParams);
                $this->userSubscriptionVariantsRepository->removeSubscribedVariants($actual);
            }
        }
    }

    public function unsubscribeEmail(ActiveRow $mailType, string $email, array $rtmParams = []): void
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
            ] + $rtmParams);

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
        return $this->getTable()->where(['user_id' => $userId, 'mail_type_id' => $mailType->id, 'user_email' => $email])->limit(1)->fetch();
    }

    public function unsubscribeUserVariant(ActiveRow $userSubscription, ActiveRow $variant, array $rtmParams = []): void
    {
        $this->userSubscriptionVariantsRepository->removeSubscribedVariant($userSubscription, $variant->id);
        if ($this->userSubscriptionVariantsRepository->subscribedVariants($userSubscription)->count('*') == 0) {
            $this->unSubscribeUser($userSubscription->mail_type, $userSubscription->user_id, $userSubscription->user_email, $rtmParams);
        }
    }

    public function getMailTypeGraphData(int $mailTypeId, \DateTime $from, \DateTime $to): Selection
    {
        return $this->getTable()
            ->select('
                count(id) AS unsubscribed_users,
                DATE(updated_at) AS label_date
            ')
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

    private function emitUserSubscribedEvent($userId, $email, $mailTypeId, $sendWelcomeEmail)
    {
        $this->emitter->emit(new HermesMessage('user-subscribed', [
            'user_id' => $userId,
            'user_email' => $email,
            'mail_type_id' => $mailTypeId,
            'send_welcome_email' => $sendWelcomeEmail,
        ]));
    }
}
