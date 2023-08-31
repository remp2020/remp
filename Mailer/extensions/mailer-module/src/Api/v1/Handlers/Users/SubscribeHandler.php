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
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class SubscribeHandler extends BaseHandler
{
    use JsonValidationTrait;

    public function __construct(
        protected UserSubscriptionsRepository $userSubscriptionsRepository,
        private ListsRepository $listsRepository,
        private ListVariantsRepository $listVariantsRepository,
        private UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository,
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
        $payload = $this->validateInput($params['raw'], __DIR__ . '/subscribe.schema.json');

        if ($this->hasErrorResponse()) {
            return $this->getErrorResponse();
        }

        try {
            $subscribedVariants = $this->processUserSubscription($payload);
        } catch (InvalidApiInputParamException $e) {
            return new JsonApiResponse($e->getCode(), [
                'status' => 'error',
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ]);
        }

        return new JsonApiResponse(200, [
            'status' => 'ok',
            'subscribed_variants' => $subscribedVariants,
        ]);
    }

    protected function processUserSubscription($payload): array
    {
        $list = $this->getList($payload);
        $variantID = $this->getVariantID($payload, $list);

        $userListSubscription = $this->userSubscriptionsRepository->subscribeUser(
            mailType: $list,
            userId: $payload['user_id'],
            email: $payload['email'],
            variantId: $variantID,
            sendWelcomeEmail: $payload['send_accompanying_emails'] ?? true,
            rtmParams: $this->getRtmParams($payload),
            forceNoVariantSubscription: $payload['force_no_variant_subscription'] ?? false,
        );

        $subscribedVariantsData = [];

        $subscribedVariants = $this->userSubscriptionVariantsRepository->subscribedVariants($userListSubscription)
            ->order('mail_type_variant.sorting')
            ->fetchAll();

        foreach ($subscribedVariants as $subscribedVariant) {
            $variant = $subscribedVariant->mail_type_variant;
            $subscribedVariantsData[] = (object) [
                'id' => $variant->id,
                'title' => $variant->title,
                'code' => $variant->code,
                'sorting' => $variant->sorting,
            ];
        }
        return $subscribedVariantsData;
    }

    /**
     * Validate and load list from $payload
     *
     * @param array $payload
     * @return ActiveRow $list - Returns mail list entity.
     * @throws InvalidApiInputParamException - Thrown if list_id or list_code are invalid (code 400) or if list is not found (code 404).
     */
    protected function getList(array $payload): ActiveRow
    {
        if (isset($payload['list_code'])) {
            $list = $this->listsRepository->findByCode($payload['list_code'])->fetch();
        } else {
            $list = $this->listsRepository->find($payload['list_id']);
        }

        if (!$list) {
            throw new InvalidApiInputParamException(
                'List with identifier ' . ($payload['list_code'] ?? $payload['list_id']) . ' not found.',
                404,
                'list_not_found',
            );
        }

        return $list;
    }

    /**
     * Validate and load variant
     *
     * @param array $payload
     * @param ActiveRow $list - Already validated $list. Used to provide default variant_id if none was provided and to validate relationship between provided variant and list.
     * @return null|int - Returns validated Variant ID. If no variant_id was provided, returns list's default variant id (can be null).
     * @throws InvalidApiInputParamException - Thrown if variant_id is invalid or doesn't belong to list (code 400) or if variant with given ID doesn't exist (code 404).
     */
    protected function getVariantID(array $payload, ActiveRow $list): ?int
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
            return $variant->id;
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
            return $variant->id;
        }

        return $list->default_variant_id;
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
