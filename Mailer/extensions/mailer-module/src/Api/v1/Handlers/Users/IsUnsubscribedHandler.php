<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class IsUnsubscribedHandler extends BaseHandler
{
    use JsonValidationTrait;

    public function __construct(
        private UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        parent::__construct();
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $payload = $this->validateInput($params['raw'], __DIR__ . '/is-unsubscribed.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        $userUnsubscribed = $this->userSubscriptionsRepository->isUserUnsubscribed($payload['user_id'], $payload['list_id']);
        $emailUnsubscribed = $this->userSubscriptionsRepository->isEmailUnsubscribed($payload['email'], $payload['list_id']);
        return new JsonApiResponse(IResponse::S200_OK, [
            'is_unsubscribed' => $userUnsubscribed && $emailUnsubscribed,
        ]);
    }
}
