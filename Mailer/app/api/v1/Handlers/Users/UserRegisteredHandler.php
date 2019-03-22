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

        /** @var ActiveRow $list */
        foreach ($lists as $list) {
            if ($list->auto_subscribe) {
                $this->userSubscriptionsRepository->subscribeUser($list, $params['user_id'], $params['email']);
            } else {
                $this->userSubscriptionsRepository->unsubscribeUser($list, $params['user_id'], $params['email']);
            }
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
