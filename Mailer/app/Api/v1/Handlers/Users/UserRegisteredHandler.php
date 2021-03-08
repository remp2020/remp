<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Models\Users\IUser;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\PostInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class UserRegisteredHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    private $userProvider;

    private $listsRepository;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository,
        ListsRepository $listsRepository,
        IUser $userProvider
    ) {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->userProvider = $userProvider;
        $this->listsRepository = $listsRepository;
    }

    public function params(): array
    {
        return [
            (new PostInputParam('email'))->setRequired(),
            (new PostInputParam('user_id'))->setRequired(),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        $lists = $this->listsRepository->all();

        $userSubscriptions = $this->userSubscriptionsRepository->findByEmail($params['email']);
        $mappedSubscriptions = [];
        foreach ($userSubscriptions as $userSubscription) {
            $mappedSubscriptions[$userSubscription->mail_type_id] = $userSubscription->subscribed;
        }

        $userID = filter_var($params['user_id'], FILTER_VALIDATE_INT);
        if ($userID === false) {
            return new JsonApiResponse(400, [
                'status' => 'error',
                'message' => "Parameter 'user_id' must be integer. Got [{$params['user_id']}]."
            ]);
        }

        /** @var ActiveRow $list */
        foreach ($lists as $list) {
            if (isset($mappedSubscriptions[$list->id])) {
                // user has already setting for this newsletter list
                continue;
            }

            if ($list->auto_subscribe) {
                $this->userSubscriptionsRepository->subscribeUser($list, $userID, $params['email']);
            } else {
                $this->userSubscriptionsRepository->unsubscribeUser($list, $userID, $params['email']);
            }
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
