<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Utils\Strings;
use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class UnSubscribeHandler extends BaseHandler
{
    use JsonValidationTrait;

    public function __construct(
        private UserSubscriptionsRepository $userSubscriptionsRepository,
        private ListsRepository $listsRepository,
        private ListVariantsRepository $listVariantsRepository
    ) {
        parent::__construct();
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
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

        if (!$list) {
            return new JsonApiResponse(404, [
                'status' => 'error',
                'code' => 'list_not_found',
                'message' => 'List with identifier ' . ($payload['list_code'] ?? $payload['list_id']) . ' not found.',
            ]);
        }

        try {
            $variant = $this->getVariant($payload, $list);
        } catch (InvalidApiInputParamException $e) {
            return new JsonApiResponse($e->getCode(), [
                'status' => 'error',
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ]);
        }

        $sendGoodbyeEmail = $payload['send_accompanying_emails'] ?? true;
        $keepListSubscription = $payload['keep_list_subscription'] ?? false;

        if ($variant) {
            $userSubscription = $this->userSubscriptionsRepository->getUserSubscription($list, $payload['user_id'], $payload['email']);
            if (!$userSubscription) {
                return new JsonApiResponse(200, ['status' => 'ok']);
            }

            $this->userSubscriptionsRepository->unsubscribeUserVariant(
                userSubscription: $userSubscription,
                variant: $variant,
                rtmParams: $this->getRtmParams($payload),
                sendGoodbyeEmail: $sendGoodbyeEmail,
                keepMailTypeSubscription: $keepListSubscription
            );
        } else {
            $this->userSubscriptionsRepository->unsubscribeUser(
                mailType: $list,
                userId: $payload['user_id'],
                email: $payload['email'],
                rtmParams: $this->getRtmParams($payload),
                sendGoodbyeEmail: $sendGoodbyeEmail,
            );
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

    protected function getVariant(array $payload, ActiveRow $list): ?ActiveRow
    {
        if (isset($payload['variant_id'])) {
            $variant = $this->listVariantsRepository->findByIdAndMailTypeId($payload['variant_id'], $list->id);
            if (!$variant) {
                throw new InvalidApiInputParamException(
                    "Variant with ID [{$payload['variant_id']}] for list [ID: {$list->id}, code: {$list->code}] was not found.",
                    404,
                    'variant_not_found',
                );
            }
            return $variant;
        }
        if (isset($payload['variant_code'])) {
            $variant = $this->listVariantsRepository->findByCodeAndMailTypeId($payload['variant_code'], $list->id);
            if (!$variant) {
                throw new InvalidApiInputParamException(
                    "Variant with code [{$payload['variant_code']}] for list [ID: {$list->id}, code: {$list->code}] was not found.",
                    404,
                    'variant_not_found',
                );
            }
            return $variant;
        }

        return $list->default_variant;
    }
}
