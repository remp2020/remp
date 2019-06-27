<?php

namespace Remp\MailerModule\Api\v1\Handlers\Users;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\ListVariantsRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;

class SubscribeHandler extends BaseHandler
{
    protected $userSubscriptionsRepository;

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

        try {
            $email = $this->getUserEmail($data);
            $userID = $this->getUserID($data);
            $list = $this->getList($data);
            $variantID = $this->getVariantID($data, $list);
        } catch (InvalidApiInputParamException $e) {
            return new JsonApiResponse($e->getCode(), ['status' => 'error', 'message' => $e->getMessage()]);
        }

        $this->userSubscriptionsRepository->subscribeUser(
            $list,
            $userID,
            $email,
            $variantID
        );

        return new JsonApiResponse(200, ['status' => 'ok']);
    }

    /**
     * Validate and load email from $params
     *
     * @param $params
     * @return string
     * @throws InvalidApiInputParamException
     */
    protected function getUserEmail($params): string
    {
        if (!isset($params['email'])) {
            throw new InvalidApiInputParamException('Required field missing: `email`.', 400);
        }
        return $params['email'];
    }

    /**
     * Validate and load user_id from $params
     *
     * @param $params
     * @return int - Returns user_id
     * @throws InvalidApiInputParamException - Thrown if user_id is not valid (code 400).
     */
    protected function getUserID($params): int
    {
        if (!isset($params['user_id'])) {
            throw new InvalidApiInputParamException('Required field missing: `user_id`.', 400);
        }
        $userID = filter_var($params['user_id'], FILTER_VALIDATE_INT);
        if ($userID === false) {
            throw new InvalidApiInputParamException(
                "Parameter `user_id` must be integer. Got [{$params['user_id']}].",
                400
            );
        }

        return $userID;
    }

    /**
     * Validate and load list from $params
     *
     * @param $params
     * @return ActiveRow $list - Returns mail list entity.
     * @throws InvalidApiInputParamException - Thrown if list_id or list_code are invalid (code 400) or if list is not found (code 404).
     */
    protected function getList($params): ActiveRow
    {
        if (!isset($params['list_id']) && !isset($params['list_code'])) {
            throw new InvalidApiInputParamException('Required field missing: `list_id` or `list_code`.', 400);
        }

        if (isset($params['list_code'])) {
            $list = $this->listsRepository->findByCode($params['list_code'])->fetch();
        } else {
            $listID = filter_var($params['list_id'], FILTER_VALIDATE_INT);
            if ($listID === false) {
                throw new InvalidApiInputParamException(
                    "Parameter 'list_id' must be integer. Got [{$params['list_id']}].",
                    400
                );
            }
            $list = $this->listsRepository->find($listID);
        }

        if ($list === false) {
            throw new InvalidApiInputParamException('List not found.', 404);
        }

        return $list;
    }

    /**
     * Validate and load variant
     *
     * @param array $params
     * @param ActiveRow $list - Already validated $list. Used to provide default variant_id if none was provided and to validate relationship between provided variant and list.
     * @return null|int - Returns validated Variant ID. If no variant_id was provided, returns list's default variant id (can be null).
     * @throws InvalidApiInputParamException - Thrown if variant_id is invalid or doesn't belong to list (code 400) or if variant with given ID doesn't exist (code 404).
     */
    protected function getVariantID(array $params, ActiveRow $list): ?int
    {
        if (!isset($params['variant_id'])) {
            return $list->default_variant_id;
        }

        $variantID = filter_var($params['variant_id'], FILTER_VALIDATE_INT);
        if ($variantID === false) {
            throw new InvalidApiInputParamException(
                "Parameter 'variant_id' must be integer. Got [{$params['variant_id']}].",
                400
            );
        }

        $variant = $this->listVariantsRepository->findByIdAndMailTypeId($variantID, $list->id);
        if ($variant === false) {
            throw new InvalidApiInputParamException(
                "Variant with ID [{$variantID}] for list [ID: {$list->id}, code: {$list->code}] was not found.",
                404
            );
        }

        return $variant->id;
    }
}
