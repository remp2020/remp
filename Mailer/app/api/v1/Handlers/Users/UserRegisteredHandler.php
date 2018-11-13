<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\User\IUser;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

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

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST, 'user_id', InputParam::REQUIRED),
        ];
    }

    public function handle($params)
    {
        $userId = $params['user_id'];

        $userList = $this->userProvider->list([$userId], 1);
        if (count($userList) === 0) {
            return new JsonApiResponse(404, ['status' => 'error', 'message' => 'Invalid user_id parameter']);
        }
        $userInfo = $userList[$userId];

        $lists = $this->listsRepository->all();

        /** @var ActiveRow $list */
        foreach ($lists as $list) {
            if ($list->auto_subscribe) {
                $this->userSubscriptionsRepository->subscribeUser($list, $userId, $userInfo['email']);
            } else {
                $this->userSubscriptionsRepository->unsubscribeUser($list, $userId, $userInfo['email']);
            }
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
