<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Users;

use League\Event\EventDispatcher;
use Nette\Database\Explorer;
use Remp\MailerModule\Events\BeforeUserEmailChangeEvent;
use Remp\MailerModule\Events\BeforeUsersDeleteEvent;
use Remp\MailerModule\Events\UserEmailChangedEvent;
use Remp\MailerModule\Events\UsersDeletedEvent;
use Remp\MailerModule\Repositories\AutoLoginTokensRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\LogConversionsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;

class UserManager
{
    public function __construct(
        private Explorer $database,
        private AutoLoginTokensRepository $autoLoginTokensRepository,
        private JobQueueRepository $jobQueueRepository,
        private LogConversionsRepository $logConversionsRepository,
        private LogsRepository $logsRepository,
        private UserSubscriptionsRepository $userSubscriptionsRepository,
        private EventDispatcher $eventDispatcher,
    ) {
    }

    public function changeEmail(string $originalEmail, string $newEmail): bool
    {
        $this->eventDispatcher->dispatch(new BeforeUserEmailChangeEvent($originalEmail, $newEmail));

        $subscriptions = $this->userSubscriptionsRepository->findByEmail($originalEmail);
        if (!count($subscriptions)) {
            return false;
        }

        foreach ($subscriptions as $subscription) {
            $this->userSubscriptionsRepository->update($subscription, ['user_email' => $newEmail]);
        }

        $this->eventDispatcher->dispatch(new UserEmailChangedEvent($originalEmail, $newEmail));
        return true;
    }

    /**
     * @param array<string> $emails
     * @throws \Exception
     */
    public function deleteUsers(array $emails): bool
    {
        foreach ($emails as $email) {
            if (strlen(trim($email)) === 0) {
                throw new \Exception('Email cannot be empty string.');
            }
        }

        $this->database->beginTransaction();
        try {
            $this->eventDispatcher->dispatch(new BeforeUsersDeleteEvent($emails));

            $deletedAutologinTokens = $this->autoLoginTokensRepository->deleteAllForEmails($emails);
            $deletedJobQueues = $this->jobQueueRepository->deleteAllByEmails($emails);

            // log conversions are internal marker; doesn't contain user data but has to be removed before mail logs
            $mailLogIds = $this->logsRepository->allForEmails($emails)->fetchPairs(null, 'id');
            $this->logConversionsRepository->deleteForMailLogs($mailLogIds);

            $deletedMailLogs = $this->logsRepository->deleteAllForEmails($emails);
            $deletedUserSubscriptions = $this->userSubscriptionsRepository->deleteAllForEmails($emails);

            $this->database->commit();
        } catch (\Exception $e) {
            $this->database->rollBack();
            throw $e;
        }

        $this->eventDispatcher->dispatch(new UsersDeletedEvent($emails));

        // nothing was removed
        if ($deletedAutologinTokens === 0 && $deletedJobQueues === 0 && $deletedMailLogs === 0 && $deletedUserSubscriptions === 0) {
            return false;
        }

        return true;
    }
}
