<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Database\Context;
use Nette\Http\IResponse;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\AutoLoginTokensRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\LogConversionsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class UserDeleteApiHandler extends BaseHandler
{
    use JsonValidationTrait;

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
        parent::__construct();

        $this->database = $database;
        $this->autoLoginTokensRepository = $autoLoginTokensRepository;
        $this->jobQueueRepository = $jobQueueRepository;
        $this->logConversionsRepository = $logConversionsRepository;
        $this->logsRepository = $logsRepository;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/user-delete.schema.json');
        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }
        $email = $payload['email'];

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

        if ($deletedAutologinTokens === 0 && $deletedJobQueues === 0 && $deletedMailLogs === 0 && $deletedUserSubscriptions === 0) {
            return new JsonApiResponse(IResponse::S404_NOT_FOUND, [
                'status' => 'error',
                'code' => 'user_not_found',
                'message' => "No user data found for email [{$email}].",
            ]);
        }

        return new JsonApiResponse(IResponse::S204_NO_CONTENT, []);
    }
}
