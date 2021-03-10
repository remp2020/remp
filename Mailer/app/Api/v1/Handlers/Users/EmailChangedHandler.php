<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\PostInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class EmailChangedHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    public function params(): array
    {
        return [
            (new PostInputParam('original_email'))->setRequired(),
            (new PostInputParam('new_email'))->setRequired(),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $originalEmail = $params['original_email'];
        $newEmail = $params['new_email'];

        $subscriptions = $this->userSubscriptionsRepository->findByEmail($originalEmail);

        if (empty($subscriptions)) {
            return new JsonApiResponse(
                404,
                ['status' => 'error', 'message' => 'No user subscriptions for email: ' . $originalEmail]
            );
        }

        foreach ($subscriptions as $subscription) {
            $this->userSubscriptionsRepository->update($subscription, ['user_email' => $newEmail]);
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
