<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class BulkSubscribeHandler extends SubscribeHandler
{
    use JsonValidationTrait;

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
        $payload = $this->validateInput($params['raw'], __DIR__ . '/bulk-subscribe.schema.json');
        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        $users = [];
        $errors = [];
        $iteration = 0;
        foreach ($payload['users'] as $item) {
            $iteration++;

            // process default parameters of users/subscribe API
            try {
                $list = $this->getList($item);
                $variantID = $this->getVariantID($item, $list);
            } catch (InvalidApiInputParamException $e) {
                $errors = array_merge($errors, ["element_" . $iteration => $e->getMessage()]);
                continue;
            }

            $users[] = [
                'email' => $item['email'],
                'user_id' => $item['user_id'],
                'list' => $list,
                'variant_id' => $variantID,
                'subscribe' => $item['subscribe'],
                'utm_params' => $item['utm_params'] ?? [],
            ];
        }

        foreach ($users as $user) {
            $utmParams = $item['utm_params'] ?? [];

            if ($user['subscribe'] === true) {
                $this->userSubscriptionsRepository->subscribeUser($user['list'], $user['user_id'], $user['email'], $user['variant_id']);
            } else {
                // if email doesn't exist, no need to unsubscribe
                if (!empty($this->userSubscriptionsRepository->findByEmail($user['email']))) {
                    $this->userSubscriptionsRepository->unsubscribeUser($user['list'], $user['user_id'], $user['email'], $utmParams);
                }
            }
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }
}
