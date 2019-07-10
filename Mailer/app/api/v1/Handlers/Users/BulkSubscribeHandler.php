<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\ListVariantsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class BulkSubscribeHandler extends SubscribeHandler
{

    public function __construct(
        UserSubscriptionsRepository $userSubscriptionsRepository,
        ListsRepository $listsRepository,
        ListVariantsRepository $listVariantsRepository
    ) {
        parent::__construct($userSubscriptionsRepository, $listsRepository, $listVariantsRepository);
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_POST_RAW, 'raw'),
        ];
    }

    public function handle($params)
    {
        try {
            $data = Json::decode($params['raw'], Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Input data was not valid JSON.']);
        }

        if (!isset($data['users'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => "Input data missing 'users' array."]);
        }

        $users = [];
        $errors = [];
        $iteration = -1;

        foreach ($data['users'] as $item) {
            $iteration++;

            // process default parameters of users/subscribe API
            try {
                $email = $this->getUserEmail($item);
                $userID = $this->getUserID($item);
                $list = $this->getList($item);
                $variantID = $this->getVariantID($item, $list);
            } catch (InvalidApiInputParamException $e) {
                $errors = array_merge($errors, ["element_" . $iteration => $e->getMessage()]);
                continue;
            }

            // process required subscribe parameter
            if (!isset($item['subscribe'])) {
                $errors = array_merge($errors, ["element_" . $iteration => 'Required field missing: `subscribe`.']);
                continue;
            }
            $subscribe = filter_var($item['subscribe'], FILTER_VALIDATE_BOOLEAN);

            // process optional parameters
            $utmParams = $item['utm_params'] ?? [];

            $users[] = [
                'email' => $email,
                'user_id' => $userID,
                'list' => $list,
                'variant_id' => $variantID,
                'subscribe' => $subscribe,
                'utm_params' => $utmParams,
            ];
        }

        if (!empty($errors)) {
            return new JsonApiResponse(400, [
                'status' => 'error',
                'message' => 'Input data contains errors. See included list of errors.',
                'errors' => $errors,
            ]);
        }

        // ready to (un)subscribe all validated users
        foreach ($users as $user) {
            if ($user['subscribe'] === true) {
                $this->userSubscriptionsRepository->subscribeUser($user['list'], $user['user_id'], $user['email'], $user['variant_id']);
            } else {
                // if email doesn't exist, no need to unsubscribe
                if (!empty($this->userSubscriptionsRepository->findByEmail($user['email']))) {
                    $this->userSubscriptionsRepository->unsubscribeUser($user['list'], $user['user_id'], $user['email'], $user['utm_params']);
                }
            }
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
