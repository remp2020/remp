<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class UnSubscribeHandler extends BaseHandler
{
    private $userSubscriptionsRepository;

    private $listsRepository;

    use JsonValidationTrait;

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
        $payload = $this->validateInput($params['raw'], __DIR__ . '/unsubscribe.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        if (isset($payload['list_code'])) {
            $list = $this->listsRepository->findByCode($payload['list_code'])->fetch();
        } else {
            $list = $this->listsRepository->find($payload['list_id']);
        }

        if ($list === false) {
            return new JsonApiResponse(404, ['status' => 'error', 'message' => 'list not found']);
        }

        if (isset($payload['variant_id'])) {
            $userSubscription = $this->userSubscriptionsRepository->getUserSubscription($list, $payload['user_id'], $payload['email']);

            if (!$userSubscription) {
                return new JsonApiResponse(200, ['status' => 'ok']);
            }

            $this->userSubscriptionsRepository->unsubscribeUserVariant($userSubscription, $payload['variant_id'], $payload['utm_params'] ?? []);
        } else {
            $this->userSubscriptionsRepository->unsubscribeUser($list, $payload['user_id'], $payload['email'], $payload['utm_params'] ?? []);
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
