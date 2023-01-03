<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Users;

use Nette\Database\Explorer;
use Remp\MailerModule\Repositories\AutoLoginTokensRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\LogConversionsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;

class UserManager
{
    private $database;

    private $autoLoginTokensRepository;

    private $jobQueueRepository;

    private $logConversionsRepository;

    private $logsRepository;

    private $userSubscriptionsRepository;

    public function __construct(
        Explorer $database,
        AutoLoginTokensRepository $autoLoginTokensRepository,
        JobQueueRepository $jobQueueRepository,
        LogConversionsRepository $logConversionsRepository,
        LogsRepository $logsRepository,
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        $this->database = $database;
        $this->autoLoginTokensRepository = $autoLoginTokensRepository;
        $this->jobQueueRepository = $jobQueueRepository;
        $this->logConversionsRepository = $logConversionsRepository;
        $this->logsRepository = $logsRepository;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    /**
     * @param array<string> $emails
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

        // nothing was removed
        if ($deletedAutologinTokens === 0 && $deletedJobQueues === 0 && $deletedMailLogs === 0 && $deletedUserSubscriptions === 0) {
            return false;
        }

        return true;
    }
}
