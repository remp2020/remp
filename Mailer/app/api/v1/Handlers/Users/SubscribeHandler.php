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
    private $userSubscriptionsRepository;

    private $listsRepository;

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
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Input data was not valid JSON.']);
        }

        if (!isset($data['email'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Required field missing: `email`.']);
        }
        $email = $data['email'];

        // validate user_id
        if (!isset($data['user_id'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Required field missing: `user_id`.']);
        }
        $userID = filter_var($data['user_id'], FILTER_VALIDATE_INT);
        if ($userID === false) {
            return new JsonApiResponse(400, [
                'status' => 'error',
                'message' => "Parameter 'user_id' must be integer. Got [{$data['user_id']}]."
            ]);
        }

        // validate & load list
        if (!isset($data['list_id']) && !isset($data['list_code'])) {
            return new JsonApiResponse(400, [
                'status' => 'error',
                'message' => 'Required field missing: `list_id` or `list_code`.',
            ]);
        }
        if (isset($data['list_code'])) {
            $list = $this->listsRepository->findByCode($data['list_code'])->fetch();
        } else {
            $listID = filter_var($data['list_id'], FILTER_VALIDATE_INT);
            if ($listID === false) {
                return new JsonApiResponse(400, [
                    'status' => 'error',
                    'message' => "Parameter 'list_id' must be integer. Got [{$data['list_id']}]."
                ]);
            }

            $list = $this->listsRepository->find($listID);
        }
        if ($list === false) {
            return new JsonApiResponse(404, ['status' => 'error', 'message' => 'List not found.']);
        }

        // validate & load variant
        if (isset($data['variant_id'])) {
            $variantID = filter_var($data['variant_id'], FILTER_VALIDATE_INT);
            if ($variantID === false) {
                return new JsonApiResponse(400, [
                    'status' => 'error',
                    'message' => "Parameter 'variant_id' must be integer. Got [{$data['variant_id']}]."
                ]);
            }

            $variant = $this->listVariantsRepository->findByIdAndMailTypeId($variantID, $list->id);
            if ($variant === false) {
                return new JsonApiResponse(404, [
                    'status' => 'error',
                    'message' => "Variant with ID [{$variantID}] for list [ID: {$list->id}, code: {$list->code}] was not found.",
                ]);
            }

            $variantID = $variant->id;
        } else {
            $variantID = $list->default_variant_id;
        }

        // ready to subscribe
        $this->userSubscriptionsRepository->subscribeUser($list, $userID, $email, $variantID);

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
