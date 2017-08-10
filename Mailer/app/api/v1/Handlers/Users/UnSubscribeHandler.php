<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class UnSubscribeHandler extends BaseHandler
{
    /** @var UserSubscriptionsRepository */
    private $userSubscriptionsRepository;

    public function __construct(UserSubscriptionsRepository $userSubscriptionsRepository)
    {
        parent::__construct();
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
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
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Wrong format.']);
        }

        if (!isset($data['email']) || !isset($data['list_id'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Missing parameters.']);
        }

        $userSubscription = $this->userSubscriptionsRepository->findByEmailList($data['email'], $data['list_id']);
        if ($userSubscription === false) {
            return new JsonApiResponse(404, ['status' => 'error', 'message' => 'Data not found.']);
        }

        $this->userSubscriptionsRepository->update($userSubscription, [
            'subscribed' => 0,
            'mail_type_variant_id' => null,
        ]);

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
