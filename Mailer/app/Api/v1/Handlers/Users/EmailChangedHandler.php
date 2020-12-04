<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class EmailChangedHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'original_email', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'new_email', InputParam::REQUIRED),
        ];
    }

    public function handle($params)
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
