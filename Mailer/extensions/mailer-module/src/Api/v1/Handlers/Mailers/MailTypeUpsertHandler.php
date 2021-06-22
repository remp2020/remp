<?php
declare(strict_types=1);

namespace Remp\MailerModule\Api\v1\Handlers\Mailers;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Remp\MailerModule\Api\InvalidApiInputParamException;
use Remp\MailerModule\Repositories\ListCategoriesRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Tomaj\NetteApi\Handlers\BaseHandler;
use Tomaj\NetteApi\Params\InputParam;
use Tomaj\NetteApi\Params\RawInputParam;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class MailTypeUpsertHandler extends BaseHandler
{
    private $listsRepository;

    private $listCategoriesRepository;

    public function __construct(
        ListsRepository $listsRepository,
        ListCategoriesRepository $listCategoriesRepository
    ) {
        parent::__construct();

        $this->listsRepository = $listsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
    }

    public function params(): array
    {
        return [
            new RawInputParam('raw'),
        ];
    }

    public function handle(array $params): ResponseInterface
    {
        try {
            $data = Json::decode($params['raw'], Json::FORCE_ARRAY);
        } catch (JsonException $e) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'input data was not valid JSON']);
        }

        if (!isset($data['mail_type_category_id'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Required field missing: mail_type_category_id']);
        }
        if (!$this->listCategoriesRepository->find($data['mail_type_category_id'])) {
            return new JsonApiResponse(400, ['status' => 'error', 'message' => 'Invalid mail_type_category_id provided: ' . $data['mail_type_category_id']]);
        }

        try {
            $list = $this->getList($data);
            if ($list) {
                $this->listsRepository->update($list, $data);
            } else {
                $list = $this->addList($data);
            }
        } catch (InvalidApiInputParamException $e) {
            return new JsonApiResponse($e->getCode(), [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        return new JsonApiResponse(200, [
            'status' => 'ok',
            'data' => [
                'id' => $list->id,
                'code' => $list->code,
                'title' => $list->title,
                'sorting' => $list->sorting,
                'description' => $list->description,
                'mail_from' => $list->mail_from,
                'priority' => $list->priority,
                'mail_type_category_id' => $list->mail_type_category_id,
                'locked' => (bool) $list->locked,
                'is_public' => (bool) $list->is_public,
                'public_listing' => (bool) $list->public_listing,
                'auto_subscribe' => (bool) $list->auto_subscribe,
                'image_url' => $list->image_url,
                'preview_url' => $list->preview_url,
                'page_url' => $list->page_url,
                'created_at' => $list->created_at->format(DATE_RFC3339),
                'updated_at' => $list->updated_at->format(DATE_RFC3339),
                'is_multi_variant' => (bool) $list->is_multi_variant,
                'default_variant_id' => $list->default_variant_id,
            ],
        ]);
    }

    private function getList($input)
    {
        if (isset($input['id'])) {
            return $this->listsRepository->find($input['id']);
        }

        if (isset($input['code'])) {
            return $this->listsRepository->findByCode($input['code'])->fetch();
        }

        return false;
    }

    private function getLastSortingNumberByCategory($categoryId)
    {
        $listsInCategory = $this->listsRepository->findByCategory($categoryId)
            ->order('mail_types.sorting')
            ->fetchAll();

        if (!empty($listsInCategory)) {
            return end($listsInCategory)->sorting + 1;
        }

        return 1;
    }

    private function addList($params)
    {
        $required = [
            'mail_type_category_id',
            'priority',
            'code',
            'title',
            'description',
        ];
        $missing = [];

        foreach ($required as $field) {
            if (!isset($params[$field])) {
                $missing[$field] = true;
            }
        }
        if (count($missing)) {
            throw new InvalidApiInputParamException(sprintf(
                'Required fields missing: %s',
                implode(', ', array_keys($missing))
            ), 400);
        }


        return $this->listsRepository->add(
            $params['mail_type_category_id'],
            $params['priority'],
            $params['code'],
            $params['title'],
            $params['sorting'] ?? $this->getLastSortingNumberByCategory($params['mail_type_category_id']),
            $params['auto_subscribe'] ?? false,
            $params['locked'] ?? false,
            $params['is_public'] ?? true,
            $params['description'] ?? null,
            $params['preview_url'] ?? null,
            $params['page_url'] ?? null,
            $params['image_url'] ?? null,
            $params['public_listing'] ?? true,
            $params['mail_from'] ?? null
        );
    }
}
