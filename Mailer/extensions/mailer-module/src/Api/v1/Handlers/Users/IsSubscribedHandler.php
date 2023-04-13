<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Http\IResponse;
use Remp\MailerModule\Api\JsonValidationTrait;
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

        $userSubscribed = $this->userSubscriptionsRepository->isUserSubscribed($payload['user_id'], $payload['list_id']);
        $emailSubscribed = $this->userSubscriptionsRepository->isEmailSubscribed($payload['email'], $payload['list_id']);

        $isSubscribed = $userSubscribed && $emailSubscribed;

        if ($isSubscribed && isset($payload['variant_id'])) {
            $userSubscription = $this->userSubscriptionsRepository->getTable()->where([
                'user_id' => $payload['user_id'],
                'user_email' => $payload['email'],
                'mail_type_id' => $payload['list_id'],
            ])->fetch();

            if ($userSubscription) {
                $isSubscribed = $this->userSubscriptionVariantsRepository->variantSubscribed($userSubscription, $payload['variant_id']);
            }
        }

        return new JsonApiResponse(IResponse::S200_OK, [
            'is_subscribed' => $isSubscribed,
        ]);
    }
}
