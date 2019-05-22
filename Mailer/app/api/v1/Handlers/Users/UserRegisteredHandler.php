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
            new InputParam(InputParam::TYPE_POST, 'email', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_POST, 'user_id', InputParam::REQUIRED),
        ];
    }

    public function handle($params)
    {
        $lists = $this->listsRepository->all();

        if (!empty($this->userSubscriptionsRepository->findByEmail($params['email']))) {
            return new JsonApiResponse(200, ['status' => 'ok']);
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
            if ($list->auto_subscribe) {
                $this->userSubscriptionsRepository->subscribeUser($list, $userID, $params['email']);
            } else {
                $this->userSubscriptionsRepository->unsubscribeUser($list, $userID, $params['email']);
            }
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
