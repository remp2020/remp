<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class IsSubscribedHandler extends BaseHandler
{
    use JsonValidationTrait;

    public function __construct(
        private ListsRepository $listsRepository,
        private UserSubscriptionsRepository $userSubscriptionsRepository,
        private UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository,
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
        $payload = $this->validateInput($params['raw'], __DIR__ . '/is-subscribed.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        $mailType = $this->listsRepository->find($payload['list_id']);
        if (!$mailType) {
            return new JsonApiResponse(404, [
                'status' => 'error',
                'message' => 'Mail type not found.',
                'code' => 'mail_type_not_found',
            ]);
        }
        if (!$mailType->is_external && !isset($payload['user_id'])) {
            return new JsonApiResponse(400, [
                'status' => 'error',
                'message' => 'For given non-external mail type, user_id parameter is required.',
                'code' => 'required_user_id',
            ]);
        }

        if (isset($payload['user_id'])) {
            $isSubscribed = $this->userSubscriptionsRepository->isUserAndEmailSubscribed(
                userId: $payload['user_id'],
                email: $payload['email'],
                mailTypeId: $payload['list_id'],
            );
        } else {
            $isSubscribed = $this->userSubscriptionsRepository->isEmailSubscribed($payload['email'], $payload['list_id']);
        }

        if ($isSubscribed && isset($payload['variant_id'])) {
            $where = [
                'user_id' => $payload['user_id'] ?? null,
                'user_email' => $payload['email'],
                'mail_type_id' => $payload['list_id']
            ];
            $userSubscription = $this->userSubscriptionsRepository->getTable()
                ->where(array_filter($where))
                ->fetch();

            if ($userSubscription) {
                $isSubscribed = $this->userSubscriptionVariantsRepository->variantSubscribed($userSubscription, $payload['variant_id']);
            }
        }

        return new JsonApiResponse(IResponse::S200_OK, [
            'is_subscribed' => $isSubscribed,
        ]);
    }
}
