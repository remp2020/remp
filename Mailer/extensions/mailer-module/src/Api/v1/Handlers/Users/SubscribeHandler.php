<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Api\JsonValidationTrait;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class SubscribeHandler extends BaseHandler
{
    protected $userSubscriptionsRepository;

    private $listsRepository;

    private $listVariantsRepository;

    use JsonValidationTrait;

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
            $this->processUserSubscription($payload);
        } catch (InvalidApiInputParamException $e) {
            return new JsonApiResponse($e->getCode(), ['status' => 'error', 'message' => $e->getMessage()]);
        }

        return new JsonApiResponse(200, ['status' => 'ok']);
    }

    protected function processUserSubscription($payload)
    {
        $email = $payload['email'];
        $userID = $payload['user_id'];
        $list = $this->getList($payload);
        $variantID = $this->getVariantID($payload, $list);
        $sendWelcomeEmail = $payload['send_accompanying_emails'] ?? true;

        $this->userSubscriptionsRepository->subscribeUser(
            $list,
            $userID,
            $email,
            $variantID,
            $sendWelcomeEmail
        );
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

        if ($list === false) {
            throw new InvalidApiInputParamException('List not found.', 404);
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
        if (!isset($payload['variant_id'])) {
            return $list->default_variant_id;
        }

        $variant = $this->listVariantsRepository->findByIdAndMailTypeId($payload['variant_id'], $list->id);
        if ($variant === false) {
            throw new InvalidApiInputParamException(
                "Variant with ID [{$payload['variant_id']}] for list [ID: {$list->id}, code: {$list->code}] was not found.",
                404
            );
        }

        return $variant->id;
    }
}
