<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\Strings;
use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Api\JsonValidationTrait;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class BulkSubscribeHandler extends SubscribeHandler
{
    use JsonValidationTrait;

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
                'force_no_variant_subscription' => $item['force_no_variant_subscription'] ?? false,
            ];
        }

        foreach ($users as $user) {
            $rtmParams = $item['rtm_params'] ?? [];

            if ($user['subscribe'] === true) {
                $this->userSubscriptionsRepository->subscribeUser(
                    mailType: $user['list'],
                    userId: $user['user_id'],
                    email: $user['email'],
                    variantId: $user['variant_id'],
                    sendWelcomeEmail: $user['send_accompanying_emails'],
                    rtmParams: $rtmParams,
                    forceNoVariantSubscription: $user['force_no_variant_subscription'],
                );
            } else {
                // if email doesn't exist, no need to unsubscribe
                if (!empty($this->userSubscriptionsRepository->findByEmail($user['email']))) {
                    $this->userSubscriptionsRepository->unsubscribeUser(
                        mailType: $user['list'],
                        userId: $user['user_id'],
                        email: $user['email'],
                        rtmParams: $rtmParams,
                        sendGoodbyeEmail: $user['send_accompanying_emails'],
                    );
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
