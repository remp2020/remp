<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class UnSubscribeHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    private $listsRepository;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository,
        ListsRepository $listsRepository
    ) {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->listsRepository = $listsRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST_RAW, 'raw')
        ];
    }

    public function handle($params)
    {
        try {
            $data = Json::decode($params['raw'], Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'input data was not valid JSON']);
        }

        if (!isset($data['email'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'required field missing: email']);
        }
        if (!isset($data['user_id'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'required field missing: user_id']);
        }
        if (!isset($data['list_id'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'required field missing: list_id']);
        }

        $list = $this->listsRepository->find($data['list_id']);
        if ($list === false) {
            return new JsonApiResponse(404, ['status' => 'error', 'message' => 'list not found: ' . $data['list_id']]);
        }

        $this->userSubscriptionsRepository->unsubscribeUser($list, $data['user_id'], $data['email'], $data['utm_params'] ?? []);

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
