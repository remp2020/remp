<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Users;

use Nette\Database\Context;
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
        Context $database,
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

    public function deleteUser(string $email): bool
    {
        $this->database->beginTransaction();

        try {
            $deletedAutologinTokens = $this->autoLoginTokensRepository->deleteAllForEmail($email);
            $deletedJobQueues = $this->jobQueueRepository->deleteAllByEmail($email);

            // log conversions are internal marker; doesn't contain user data but has to be removed before mail logs
            $mailLogIds = $this->logsRepository->allForEmail($email)->fetchPairs(null, 'id');
            $this->logConversionsRepository->deleteForMailLogs($mailLogIds);

            $deletedMailLogs = $this->logsRepository->deleteAllForEmail($email);
            $deletedUserSubscriptions = $this->userSubscriptionsRepository->deleteAllForEmail($email);

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
