<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\ListVariantsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class SubscribeHandler extends BaseHandler
{
    /** @var UserSubscriptionsRepository */
    private $userSubscriptionsRepository;

    /** @var ListsRepository */
    private $listsRepository;

    /** @var ListVariantsRepository */
    private $listVariantsRepository;

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository,
        ListsRepository $listsRepository,
        ListVariantsRepository $listVariantsRepository
    ) {
        parent::__construct();

        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->listsRepository = $listsRepository;
        $this->listVariantsRepository = $listVariantsRepository;
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

        if (isset($data['variant_id'])) {
            $variant = $this->listVariantsRepository->find($data['variant_id']);
            if ($variant === false || $variant->mail_type_id != $data['list_id']) {
                return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Wrong parameter.']);
            }
            $variantId = $variant->id;
        } else {
            $list = $this->listsRepository->find($data['list_id']);
            if ($list === false) {
                return new JsonApiResponse(404, ['status' => 'error', 'message' => 'List not found.']);
            }
            $variantId = $list->default_variant_id;
        }

        $this->userSubscriptionsRepository->subscribeUser($list, $data['user_id'], $data['email'], $variantId);

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
