<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Psr\Log\LoggerInterface;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Models\Users\UserManager;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class UserDeleteApiHandler extends BaseHandler
{
    use JsonValidationTrait;

    private $logger;

    private $userManager;

    public function __construct(
        LoggerInterface $logger,
        UserManager $userManager
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->userManager = $userManager;
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

        try {
            $result = $this->userManager->deleteUser($email);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return new JsonApiResponse(IResponse::S500_INTERNAL_SERVER_ERROR, []);
        }

        if ($result === false) {
            return new JsonApiResponse(IResponse::S404_NOT_FOUND, [
                'status' => 'error',
                'code' => 'user_not_found',
                'message' => "No user data found for email [{$email}].",
            ]);
        }

        return new JsonApiResponse(IResponse::S204_NO_CONTENT, []);
    }
}
