<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\Strings;
use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

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

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
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
                'rtm_params' => $this->getRtmParams($item),
                'send_accompanying_emails' => $item['send_accompanying_emails'] ?? true,
            ];
        }

        foreach ($users as $user) {
            $rtmParams = $item['rtm_params'] ?? [];

            if ($user['subscribe'] === true) {
                $this->userSubscriptionsRepository->subscribeUser($user['list'], $user['user_id'], $user['email'], $user['variant_id'], $user['send_accompanying_emails']);
            } else {
                // if email doesn't exist, no need to unsubscribe
                if (!empty($this->userSubscriptionsRepository->findByEmail($user['email']))) {
                    $this->userSubscriptionsRepository->unsubscribeUser($user['list'], $user['user_id'], $user['email'], $rtmParams);
                }
            }
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }

    // function that primary loads rtm parameters but fallbacks to utm if rtm are not present
    private function getRtmParams($payload)
    {
        $rtmParams = [];
        foreach ($payload['rtm_params'] ?? $payload['utm_params'] ?? [] as $key => $value) {
            if (Strings::startsWith($key, 'utm_')) {
                $rtmParams['rtm_' . substr($key, 4)] = $value;
            } else {
                $rtmParams[$key] = $value;
            }
        }
        return $rtmParams;
    }
}
